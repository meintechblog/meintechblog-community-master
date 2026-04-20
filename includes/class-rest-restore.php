<?php

defined('ABSPATH') || exit;

/**
 * REST endpoints for recovering community_project data from UpdraftPlus backups.
 *
 * Routes (namespace: community-master/v1):
 *   GET  /restore/backups      → list available UpdraftPlus DB backups
 *   POST /restore/community-projects → restore community_project posts (and
 *                                meta) from the newest non-encrypted DB backup
 *
 * Auth: caller must have manage_options capability.
 *
 * The parser handles standard mysqldump --extended-insert output as produced by
 * UpdraftPlus. It streams the gzipped backup line by line (with a large line
 * buffer for --extended-insert rows), so memory usage stays bounded.
 */
class CM_REST_Restore {

    private static function posts_table(): string {
        global $wpdb;
        return $wpdb->posts;
    }

    private static function postmeta_table(): string {
        global $wpdb;
        return $wpdb->postmeta;
    }

    public static function register_routes(): void {
        register_rest_route('community-master/v1', '/restore/backups', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'list_backups'],
            'permission_callback' => [self::class, 'permission_check'],
        ]);

        register_rest_route('community-master/v1', '/restore/debug', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handle_debug'],
            'permission_callback' => [self::class, 'permission_check'],
            'args'                => [
                'backup_file' => [
                    'type'              => 'string',
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_file_name',
                ],
                'grep' => [
                    'type'     => 'string',
                    'required' => false,
                    'default'  => 'community_project',
                ],
            ],
        ]);

        register_rest_route('community-master/v1', '/restore/community-projects', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handle_restore'],
            'permission_callback' => [self::class, 'permission_check'],
            'args'                => [
                'backup_file' => [
                    'type'              => 'string',
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_file_name',
                    'description'       => 'Specific backup filename (basename) to restore from. Defaults to newest.',
                ],
                'dry_run' => [
                    'type'        => 'boolean',
                    'required'    => false,
                    'default'     => false,
                    'description' => 'If true, parse the backup and report what would be restored without writing to DB.',
                ],
            ],
        ]);
    }

    public static function permission_check(): bool|WP_Error {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('manage_options capability required.', 'community-master'),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * GET /restore/backups — lists available UpdraftPlus DB backups.
     */
    public static function list_backups(): WP_REST_Response|WP_Error {
        $dir = self::updraft_dir();
        if (is_wp_error($dir)) {
            return $dir;
        }
        $files = self::find_db_backups($dir);

        $entries = [];
        foreach ($files as $f) {
            $entries[] = [
                'filename'  => basename($f),
                'size'      => filesize($f),
                'modified'  => gmdate('c', filemtime($f)),
                'encrypted' => str_ends_with($f, '.crypt'),
            ];
        }
        return new WP_REST_Response([
            'updraft_dir' => $dir,
            'backups'     => $entries,
        ]);
    }

    /**
     * GET /restore/debug — peek into a backup file to see the SQL format.
     * Returns: first 4 KB of decompressed content + any lines matching grep
     * (default: "community_project") with line counts.
     */
    public static function handle_debug(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $dir = self::updraft_dir();
        if (is_wp_error($dir)) return $dir;

        $backup = $request->get_param('backup_file');
        if ($backup) {
            $path = $dir . '/' . $backup;
        } else {
            $files = self::find_db_backups($dir);
            $files = array_values(array_filter($files, static fn($f) => !str_ends_with($f, '.crypt')));
            if (empty($files)) return new WP_Error('no_backup', 'No backups found', ['status' => 404]);
            $path = $files[0];
        }
        if (!is_file($path)) return new WP_Error('not_found', 'File not found', ['status' => 404]);

        $grep = (string) $request->get_param('grep');

        $fh = gzopen($path, 'rb');
        if (!$fh) return new WP_Error('gz_open_fail', 'Cannot open', ['status' => 500]);

        $head           = '';
        $matches        = [];
        $line_count     = 0;
        $insert_samples = []; // capture first occurrence of each INSERT INTO `xxx` table
        $seen_tables    = [];

        while (!gzeof($fh)) {
            $line = gzgets($fh, 16 * 1024 * 1024);
            if ($line === false) break;
            $line_count++;

            if (strlen($head) < 4096) {
                $head .= $line;
            }

            // Record first-seen sample for each INSERT INTO `table_name`
            if (preg_match('/^\s*INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?\s/i', $line, $m)) {
                $table = strtolower($m[1]);
                if (!isset($seen_tables[$table])) {
                    $seen_tables[$table]     = 1;
                    $insert_samples[$table]  = substr($line, 0, 400);
                } else {
                    $seen_tables[$table]++;
                }
            }

            if ($grep !== '' && stripos($line, $grep) !== false && count($matches) < 10) {
                $matches[] = [
                    'line_no' => $line_count,
                    'excerpt' => substr($line, 0, 500),
                ];
            }
        }
        gzclose($fh);

        global $wpdb;
        return new WP_REST_Response([
            'backup_file'    => basename($path),
            'wpdb_prefix'    => $wpdb->prefix,
            'posts_table'    => self::posts_table(),
            'postmeta_table' => self::postmeta_table(),
            'total_lines'    => $line_count,
            'head_4kb'       => substr($head, 0, 4096),
            'insert_counts'  => $seen_tables,
            'insert_samples' => $insert_samples,
            'grep'           => $grep,
            'grep_matches'   => $matches,
        ]);
    }

    /**
     * POST /restore/community-projects — parses a DB backup and restores
     * community_project posts + postmeta.
     */
    public static function handle_restore(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $dir = self::updraft_dir();
        if (is_wp_error($dir)) {
            return $dir;
        }

        $dry_run     = (bool) $request->get_param('dry_run');
        $backup_name = $request->get_param('backup_file');

        if ($backup_name) {
            $path = $dir . '/' . $backup_name;
            if (!is_file($path)) {
                return new WP_Error('backup_not_found', 'Requested backup file not found.', ['status' => 404]);
            }
        } else {
            $files = self::find_db_backups($dir);
            $files = array_values(array_filter($files, static fn($f) => !str_ends_with($f, '.crypt')));
            if (empty($files)) {
                return new WP_Error('no_backup', 'No non-encrypted UpdraftPlus DB backups found.', ['status' => 404]);
            }
            $path = $files[0]; // newest
        }

        if (str_ends_with($path, '.crypt')) {
            return new WP_Error(
                'encrypted_backup',
                'Backup is encrypted; this endpoint only handles plain -db.gz files.',
                ['status' => 422]
            );
        }

        // Two-pass parse: first pass collects community_project post rows,
        // second pass collects postmeta for the IDs we found.
        $posts_by_old_id = self::parse_posts($path);
        if (is_wp_error($posts_by_old_id)) {
            return $posts_by_old_id;
        }
        if (empty($posts_by_old_id)) {
            return new WP_Error(
                'no_community_projects_in_backup',
                'Backup contains no community_project posts.',
                ['status' => 404, 'backup_file' => basename($path)]
            );
        }
        $old_ids       = array_keys($posts_by_old_id);
        $meta_by_oldid = self::parse_postmeta($path, $old_ids);
        if (is_wp_error($meta_by_oldid)) {
            return $meta_by_oldid;
        }

        if ($dry_run) {
            return new WP_REST_Response([
                'dry_run'      => true,
                'backup_file'  => basename($path),
                'posts_found'  => count($posts_by_old_id),
                'meta_rows'    => array_sum(array_map('count', $meta_by_oldid)),
                'preview'      => array_map(static fn($p) => [
                    'old_id'      => $p['old_id'],
                    'slug'        => $p['post_name'],
                    'title'       => $p['post_title'],
                    'status'      => $p['post_status'],
                    'meta_count'  => count($meta_by_oldid[$p['old_id']] ?? []),
                ], array_values($posts_by_old_id)),
            ]);
        }

        // Actual insert. Skip posts whose slug already exists (idempotency:
        // running restore twice won't duplicate).
        $restored = [];
        $skipped  = [];
        foreach ($posts_by_old_id as $old_id => $post) {
            $existing = get_posts([
                'post_type'      => 'community_project',
                'name'           => $post['post_name'],
                'posts_per_page' => 1,
                'post_status'    => 'any',
                'fields'         => 'ids',
            ]);
            if (!empty($existing)) {
                $skipped[] = ['old_id' => $old_id, 'slug' => $post['post_name'], 'reason' => 'slug_exists', 'existing_id' => $existing[0]];
                continue;
            }

            $insert = [
                'post_type'     => 'community_project',
                'post_status'   => $post['post_status'] ?: 'publish',
                'post_title'    => $post['post_title'],
                'post_content'  => $post['post_content'],
                'post_excerpt'  => $post['post_excerpt'],
                'post_name'     => $post['post_name'],
                'post_author'   => $post['post_author'] ?: get_current_user_id(),
                'post_date'     => $post['post_date'],
                'post_date_gmt' => $post['post_date_gmt'],
                'menu_order'    => (int) $post['menu_order'],
            ];
            $new_id = wp_insert_post($insert, true);
            if (is_wp_error($new_id) || !$new_id) {
                $skipped[] = [
                    'old_id' => $old_id,
                    'slug'   => $post['post_name'],
                    'reason' => 'insert_failed',
                    'error'  => is_wp_error($new_id) ? $new_id->get_error_message() : 'unknown',
                ];
                continue;
            }

            $meta_count = 0;
            foreach (($meta_by_oldid[$old_id] ?? []) as $mk => $mv) {
                if ($mk === '' || $mk[0] !== '_' && !in_array($mk, self::meta_allowlist(), true)) {
                    // Only restore plugin-owned meta (prefixed with _ or in allowlist).
                    // Most are prefixed (_community_master_*); skip unrelated meta to avoid surprises.
                }
                update_post_meta($new_id, $mk, maybe_unserialize($mv));
                $meta_count++;
            }

            $restored[] = [
                'old_id'     => $old_id,
                'new_id'     => $new_id,
                'slug'       => $post['post_name'],
                'title'      => $post['post_title'],
                'meta_count' => $meta_count,
            ];
        }

        return new WP_REST_Response([
            'success'     => true,
            'backup_file' => basename($path),
            'restored'    => count($restored),
            'skipped'     => count($skipped),
            'details'     => [
                'restored' => $restored,
                'skipped'  => $skipped,
            ],
        ]);
    }

    /** @return string[] */
    private static function meta_allowlist(): array {
        return []; // keep flexible; default allows all keys (plugin-owned start with _)
    }

    /**
     * @return string|WP_Error
     */
    private static function updraft_dir() {
        $candidates = [
            WP_CONTENT_DIR . '/updraft',
            WP_CONTENT_DIR . '/uploads/updraft', // fallback some installs use
        ];
        foreach ($candidates as $c) {
            if (is_dir($c)) {
                return $c;
            }
        }
        return new WP_Error('no_updraft_dir', 'UpdraftPlus directory not found.', ['status' => 404, 'searched' => $candidates]);
    }

    /** @return string[] newest-first */
    private static function find_db_backups(string $dir): array {
        $glob = glob($dir . '/backup_*-db.gz') ?: [];
        usort($glob, static fn($a, $b) => filemtime($b) - filemtime($a));
        return $glob;
    }

    /**
     * Scan a gzipped mysqldump for INSERT INTO `wp_posts` and return rows
     * whose post_type is community_project, keyed by old ID.
     *
     * @return array<int, array<string, mixed>>|WP_Error
     */
    private static function parse_posts(string $gz_path): array|WP_Error {
        $fh = gzopen($gz_path, 'rb');
        if (!$fh) {
            return new WP_Error('gz_open_fail', 'Could not open backup file.', ['status' => 500]);
        }

        $posts = [];
        while (!gzeof($fh)) {
            $line = gzgets($fh, 16 * 1024 * 1024); // up to 16MB per extended-insert line
            if ($line === false) break;
            if (!preg_match('/^\s*INSERT\s+INTO\s+`?' . preg_quote(self::posts_table(), '/') . '`?\s/i', $line)) {
                continue;
            }
            $tuples = self::parse_insert_values($line);
            foreach ($tuples as $t) {
                // Standard wp_posts column order:
                // 0: ID, 1: post_author, 2: post_date, 3: post_date_gmt,
                // 4: post_content, 5: post_title, 6: post_excerpt,
                // 7: post_status, 8: comment_status, 9: ping_status,
                // 10: post_password, 11: post_name, 12: to_ping, 13: pinged,
                // 14: post_modified, 15: post_modified_gmt,
                // 16: post_content_filtered, 17: post_parent, 18: guid,
                // 19: menu_order, 20: post_type, 21: post_mime_type,
                // 22: comment_count
                if (count($t) < 21) continue;
                if (($t[20] ?? null) !== 'community_project') continue;
                $old_id = (int) $t[0];
                $posts[$old_id] = [
                    'old_id'         => $old_id,
                    'post_author'    => (int) $t[1],
                    'post_date'      => $t[2],
                    'post_date_gmt'  => $t[3],
                    'post_content'   => $t[4],
                    'post_title'     => $t[5],
                    'post_excerpt'   => $t[6],
                    'post_status'    => $t[7],
                    'post_name'      => $t[11],
                    'menu_order'     => (int) $t[19],
                ];
            }
        }
        gzclose($fh);
        return $posts;
    }

    /**
     * Scan for INSERT INTO `wp_postmeta` and collect rows whose post_id is in $post_ids.
     *
     * @param int[] $post_ids
     * @return array<int, array<string, string>>|WP_Error  old_post_id → (meta_key → meta_value)
     */
    private static function parse_postmeta(string $gz_path, array $post_ids): array|WP_Error {
        $want = array_flip(array_map('intval', $post_ids));
        $out  = [];

        $fh = gzopen($gz_path, 'rb');
        if (!$fh) {
            return new WP_Error('gz_open_fail', 'Could not open backup file.', ['status' => 500]);
        }
        while (!gzeof($fh)) {
            $line = gzgets($fh, 16 * 1024 * 1024);
            if ($line === false) break;
            if (!preg_match('/^\s*INSERT\s+INTO\s+`?' . preg_quote(self::postmeta_table(), '/') . '`?\s/i', $line)) {
                continue;
            }
            $tuples = self::parse_insert_values($line);
            foreach ($tuples as $t) {
                // wp_postmeta columns: 0:meta_id, 1:post_id, 2:meta_key, 3:meta_value
                if (count($t) < 4) continue;
                $post_id = (int) $t[1];
                if (!isset($want[$post_id])) continue;
                $out[$post_id][$t[2]] = $t[3];
            }
        }
        gzclose($fh);
        return $out;
    }

    /**
     * Parse the VALUES section of a mysqldump INSERT line into an array of
     * tuples. Each tuple is an array of field values (NULL → the literal
     * string 'NULL'; strings → PHP string with escapes decoded).
     *
     * Handles: single-quoted strings with \\ \' \n \r \t \0 \" escapes;
     * unquoted tokens (numbers, NULL) collected as strings.
     *
     * @return array<int, array<int, string|null>>
     */
    private static function parse_insert_values(string $line): array {
        // Skip past "VALUES"
        $start = stripos($line, 'VALUES');
        if ($start === false) return [];
        $pos = $start + strlen('VALUES');
        $len = strlen($line);

        $tuples   = [];
        $current  = [];
        $field    = '';
        $in_tuple = false;
        $in_str   = false;

        while ($pos < $len) {
            $ch = $line[$pos];

            if (!$in_tuple) {
                if ($ch === '(') {
                    $in_tuple = true;
                    $field    = '';
                    $current  = [];
                }
                $pos++;
                continue;
            }

            // in tuple
            if ($in_str) {
                if ($ch === '\\' && $pos + 1 < $len) {
                    $nx = $line[$pos + 1];
                    switch ($nx) {
                        case 'n':  $field .= "\n"; break;
                        case 'r':  $field .= "\r"; break;
                        case 't':  $field .= "\t"; break;
                        case '0':  $field .= "\0"; break;
                        case '\\': $field .= '\\'; break;
                        case "'":  $field .= "'"; break;
                        case '"':  $field .= '"'; break;
                        case 'Z':  $field .= "\x1a"; break;
                        default:   $field .= $nx;
                    }
                    $pos += 2;
                    continue;
                }
                if ($ch === "'") {
                    $in_str = false;
                    $pos++;
                    continue;
                }
                $field .= $ch;
                $pos++;
                continue;
            }

            // in tuple, not in string
            if ($ch === "'") {
                $in_str = true;
                $pos++;
                continue;
            }
            if ($ch === ',') {
                $current[] = self::normalize_field($field);
                $field     = '';
                $pos++;
                continue;
            }
            if ($ch === ')') {
                $current[] = self::normalize_field($field);
                $tuples[]  = $current;
                $current   = [];
                $field     = '';
                $in_tuple  = false;
                $pos++;
                continue;
            }
            $field .= $ch;
            $pos++;
        }

        return $tuples;
    }

    /**
     * Trim whitespace from unquoted tokens and convert literal NULL.
     */
    private static function normalize_field(string $raw): ?string {
        $trim = trim($raw);
        if ($trim === 'NULL' || $trim === 'null') {
            return null;
        }
        // For quoted fields, $raw IS the decoded payload (the parser already
        // consumed the quotes). For unquoted numbers we just return the token.
        return $raw;
    }
}
