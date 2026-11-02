<?php

defined('ABSPATH') || exit;

/**
 * REST endpoints for self-updating the plugin from GitHub releases.
 *
 * Routes (namespace: community-master/v1):
 *   GET  /update-check   → installed vs latest version
 *   POST /self-update    → download + install latest release via Plugin_Upgrader
 *
 * Auth: Application Password (or any auth that carries update_plugins capability).
 */
class CM_REST_Self_Update {

    private const GITHUB_REPO = 'meintechblog/meintechblog-community-master';
    private const LOCK_KEY    = 'community_master_upgrade_lock';
    private const LOCK_TTL    = 5 * MINUTE_IN_SECONDS;

    /**
     * Register both REST routes. Hook on rest_api_init.
     */
    public static function register_routes(): void {
        register_rest_route('community-master/v1', '/update-check', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handle_update_check'],
            'permission_callback' => [self::class, 'permission_check'],
        ]);

        register_rest_route('community-master/v1', '/self-update', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handle_self_update'],
            'permission_callback' => [self::class, 'permission_check'],
            'args'                => [
                'version' => [
                    'type'              => 'string',
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'Target release tag (e.g. v1.4.1). Defaults to latest.',
                ],
            ],
        ]);
    }

    /**
     * Auth: caller must have update_plugins capability (Application Password carries caps).
     */
    public static function permission_check(): bool|WP_Error {
        if (!current_user_can('update_plugins')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to update plugins.', 'community-master'),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * GET /update-check — no side effects.
     */
    public static function handle_update_check(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $release = self::fetch_release('latest');
        if (is_wp_error($release)) {
            return $release;
        }

        $latest_tag = ltrim((string) ($release['tag_name'] ?? ''), 'v');
        $installed  = COMMUNITY_MASTER_VERSION;

        return new WP_REST_Response([
            'installed'        => $installed,
            'latest'           => $latest_tag,
            'update_available' => version_compare($installed, $latest_tag, '<'),
            'download_url'     => self::extract_zip_asset_url($release),
            'release_url'      => $release['html_url'] ?? null,
        ]);
    }

    /**
     * POST /self-update — downloads + installs release via Plugin_Upgrader.
     */
    public static function handle_self_update(WP_REST_Request $request): WP_REST_Response|WP_Error {
        // Prevent concurrent upgrades.
        if (get_transient(self::LOCK_KEY)) {
            return new WP_Error(
                'upgrade_in_progress',
                __('Another upgrade is already running. Try again in a few minutes.', 'community-master'),
                ['status' => 409]
            );
        }
        set_transient(self::LOCK_KEY, time(), self::LOCK_TTL);

        try {
            $requested = $request->get_param('version') ?: 'latest';
            $release   = self::fetch_release($requested);
            if (is_wp_error($release)) {
                return $release;
            }

            $zip_url = self::extract_zip_asset_url($release);
            if (!$zip_url) {
                return new WP_Error(
                    'release_missing_asset',
                    __('Release has no installable ZIP asset.', 'community-master'),
                    ['status' => 422]
                );
            }

            // Hard-validate the host before handing the URL to the upgrader.
            if (!self::is_trusted_github_url($zip_url)) {
                return new WP_Error(
                    'untrusted_source',
                    __('Download URL is not from the configured GitHub repo.', 'community-master'),
                    ['status' => 422]
                );
            }

            $old_version = COMMUNITY_MASTER_VERSION;
            $new_version = ltrim((string) ($release['tag_name'] ?? ''), 'v');

            // Load WordPress upgrader stack.
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once COMMUNITY_MASTER_DIR . 'includes/class-silent-upgrader-skin.php';

            // Rename unpacked folder to `community-master` regardless of what GitHub
            // named it (zipball folders are `{repo}-{sha}`; release asset ZIPs may
            // already have the correct name if built with the project's build script).
            $rename_filter = static function ($source, $remote_source, $upgrader_instance) {
                global $wp_filesystem;
                $target = trailingslashit($remote_source) . 'community-master/';
                if ($source === $target) {
                    return $source;
                }
                if ($wp_filesystem && $wp_filesystem->move($source, $target, true)) {
                    return $target;
                }
                return $source;
            };
            add_filter('upgrader_source_selection', $rename_filter, 10, 3);

            $skin     = new CM_Silent_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);

            // Use install() with overwrite so the existing folder is replaced, independent
            // of whether the plugin is currently registered in WP's updates table.
            $result = $upgrader->install($zip_url, ['overwrite_package' => true]);

            remove_filter('upgrader_source_selection', $rename_filter, 10);

            if (is_wp_error($result)) {
                return new WP_Error(
                    'upgrade_failed',
                    $result->get_error_message(),
                    ['status' => 500, 'messages' => $skin->get_messages()]
                );
            }

            if ($result === false) {
                return new WP_Error(
                    'upgrade_failed',
                    __('Plugin_Upgrader returned false. Check filesystem permissions.', 'community-master'),
                    ['status' => 500, 'messages' => $skin->get_messages()]
                );
            }

            // Ensure plugin stays active and rewrite rules reflect the new code.
            $plugin_file = 'community-master/community-master.php';
            if (!is_plugin_active($plugin_file)) {
                activate_plugin($plugin_file);
            }
            flush_rewrite_rules();

            return new WP_REST_Response([
                'success'     => true,
                'old_version' => $old_version,
                'new_version' => $new_version,
                'messages'    => $skin->get_messages(),
            ]);
        } finally {
            delete_transient(self::LOCK_KEY);
        }
    }

    /**
     * Fetch a GitHub release by tag ("latest" for the default release).
     *
     * @return array<string, mixed>|WP_Error
     */
    private static function fetch_release(string $tag): array|WP_Error {
        $path = ($tag === 'latest')
            ? 'releases/latest'
            : 'releases/tags/' . rawurlencode($tag);

        $url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/' . $path;

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'community-master-self-update/' . COMMUNITY_MASTER_VERSION,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new WP_Error(
                'github_api_error',
                sprintf(__('GitHub API returned HTTP %d', 'community-master'), $code),
                ['status' => 502]
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            return new WP_Error(
                'github_api_error',
                __('Unexpected response from GitHub API.', 'community-master'),
                ['status' => 502]
            );
        }

        return $body;
    }

    /**
     * Pick the first ZIP asset URL from a release payload, falling back to zipball_url.
     *
     * @param array<string, mixed> $release
     */
    private static function extract_zip_asset_url(array $release): ?string {
        $assets = $release['assets'] ?? [];
        if (is_array($assets)) {
            foreach ($assets as $asset) {
                $url = $asset['browser_download_url'] ?? '';
                if (is_string($url) && str_ends_with($url, '.zip')) {
                    return $url;
                }
            }
        }
        $zipball = $release['zipball_url'] ?? null;
        return is_string($zipball) ? $zipball : null;
    }

    /**
     * Verify the URL host is github.com or objects.githubusercontent.com and references our repo.
     */
    private static function is_trusted_github_url(string $url): bool {
        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || empty($parts['path'])) {
            return false;
        }
        $host = strtolower($parts['host']);
        $allowed_hosts = ['github.com', 'api.github.com', 'codeload.github.com', 'objects.githubusercontent.com'];
        if (!in_array($host, $allowed_hosts, true)) {
            return false;
        }
        // For github.com/codeload.github.com the path must start with our repo.
        // objects.githubusercontent.com uses signed URLs without the repo in the path,
        // which is fine — it's GitHub's release asset CDN and only reachable via a
        // preceding API/redirect from a verified repo.
        if ($host === 'github.com' || $host === 'codeload.github.com' || $host === 'api.github.com') {
            return str_starts_with($parts['path'], '/' . self::GITHUB_REPO . '/');
        }
        return true;
    }
}

