<?php

defined('ABSPATH') || exit;

/**
 * Main plugin orchestrator.
 *
 * Singleton that wires all components via WordPress hooks.
 */
class Community_Master {

    /** @var self|null */
    private static ?self $instance = null;

    /**
     * Return the singleton instance.
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor -- hooks all components.
     */
    private function __construct() {
        add_action('init', [CM_CPT_Project::class, 'register']);
        add_action('init', [CM_CPT_Project::class, 'register_meta_fields']);
        add_action('init', [self::class, 'register_rewrites']);
        add_filter('query_vars', [self::class, 'register_query_vars']);
        add_action('plugins_loaded', [self::class, 'maybe_flush_rewrites']);
        add_filter('rest_pre_insert_community_project', [CM_CPT_Project::class, 'validate_rest_github_url'], 10, 2);
        add_action('rest_api_init', [CM_CPT_Project::class, 'register_rest_fields']);
        add_action('rest_api_init', [CM_REST_Self_Update::class, 'register_routes']);

        new CM_Meta_Boxes();
        new CM_Admin_Columns();
        new CM_Shortcode();

        add_action('admin_menu', [$this, 'add_view_page_link']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register deep-link rewrite: /community-master/<slug>/ → grid page + query var.
     */
    public static function register_rewrites(): void {
        add_rewrite_rule(
            '^community-master/([^/]+)/?$',
            'index.php?pagename=community-master&community_project_slug=$matches[1]',
            'top'
        );
    }

    /**
     * Whitelist the community_project_slug query var so WP carries it to the shortcode.
     *
     * @param string[] $vars
     * @return string[]
     */
    public static function register_query_vars(array $vars): array {
        $vars[] = 'community_project_slug';
        return $vars;
    }

    /**
     * Flush rewrite rules once per plugin version bump. Activation hook covers
     * first install / reactivation; this covers in-place upgrades (git pull).
     */
    public static function maybe_flush_rewrites(): void {
        if (get_option('community_master_version') !== COMMUNITY_MASTER_VERSION) {
            self::register_rewrites();
            flush_rewrite_rules();
            update_option('community_master_version', COMMUNITY_MASTER_VERSION);
        }
    }

    /**
     * Add a "View Page" submenu link under Community Master.
     */
    public function add_view_page_link(): void {
        global $submenu;

        $page = get_page_by_path('community-master');
        if ($page) {
            $url = get_permalink($page);
        } else {
            $url = home_url('/community-master/');
        }

        $submenu['edit.php?post_type=community_project'][] = [
            'Seite anzeigen ↗',
            'edit_community_projects',
            $url,
        ];
    }

    /**
     * Add settings submenu page.
     */
    public function add_settings_page(): void {
        add_submenu_page(
            'edit.php?post_type=community_project',
            __('Einstellungen', 'community-master'),
            __('Einstellungen', 'community-master'),
            'manage_options',
            'community-master-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings(): void {
        register_setting('community_master_settings', 'community_master_intro', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => 'Hier findest du unsere Community-Projekte — Open-Source-Tools zum Selbsthosten. Jedes Projekt hat ein eigenes GitHub-Repository mit Dokumentation und One-Line-Installer.',
        ]);
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Community Master — Einstellungen', 'community-master'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('community_master_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="community_master_intro"><?php echo esc_html__('Intro-Text', 'community-master'); ?></label>
                        </th>
                        <td>
                            <textarea id="community_master_intro" name="community_master_intro" rows="4" cols="80" class="large-text"><?php echo esc_textarea(get_option('community_master_intro', 'Hier findest du unsere Community-Projekte — Open-Source-Tools zum Selbsthosten. Jedes Projekt hat ein eigenes GitHub-Repository mit Dokumentation und One-Line-Installer.')); ?></textarea>
                            <p class="description"><?php echo esc_html__('Wird oben auf der Community-Master-Seite angezeigt.', 'community-master'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
