# Pitfalls Research: Community Master WordPress Plugin

**Domain:** WordPress Plugin Development (Custom Post Type + REST API)
**Researched:** 2026-03-24
**Overall confidence:** HIGH (based on official WordPress developer docs + ecosystem reports)

---

## Critical Pitfalls

Mistakes that cause security breaches, data loss, or rewrites.

### Pitfall 1: Missing or Broken Nonce Verification (CSRF)

**What goes wrong:** Forms and AJAX handlers process requests without verifying they originated from the site. Attackers craft malicious links/forms that trick authenticated admins into creating, editing, or deleting community projects.

**Why it happens:** Developers add sanitization and validation but forget to generate and check nonces. Or they verify the nonce but skip sanitizing the nonce value itself before passing it to `wp_verify_nonce()`.

**Consequences:** CSRF attacks. An attacker can manipulate community project data through any logged-in administrator's browser session. This is the #2 vulnerability type in the WordPress ecosystem (17% of all CVEs).

**Prevention:**
- Every form: `wp_nonce_field('community_master_action', 'community_master_nonce')`
- Every handler: sanitize then verify:
  ```php
  $nonce = isset($_POST['community_master_nonce'])
      ? sanitize_text_field(wp_unslash($_POST['community_master_nonce']))
      : '';
  if (!wp_verify_nonce($nonce, 'community_master_action')) {
      wp_die('Security check failed');
  }
  ```
- REST API routes: WordPress handles nonce via `X-WP-Nonce` header automatically when using `permission_callback`

**Detection:** Code review grep for `$_POST`, `$_GET`, `$_REQUEST` without adjacent `wp_verify_nonce`. Any form without `wp_nonce_field`.

**Phase mapping:** Phase 1 (CPT Registration) -- bake into every admin handler from day one.

---

### Pitfall 2: Inadequate REST API Permission Callbacks

**What goes wrong:** REST API endpoints are registered with `'permission_callback' => '__return_true'` or no permission callback at all, exposing CRUD operations to unauthenticated users. Since Community Master is explicitly designed for Claude to create projects via API, this is the highest-risk surface.

**Why it happens:** During development, developers disable auth to simplify testing, then forget to re-enable it. Or they check `is_user_logged_in()` but not specific capabilities.

**Consequences:** Anyone on the internet can create, modify, or delete community projects. WordPress Application Passwords grant full user capabilities, so a leaked password means full write access.

**Prevention:**
- Always use capability-based checks in `permission_callback`:
  ```php
  'permission_callback' => function() {
      return current_user_can('edit_community_projects');
  }
  ```
- For the CPT's built-in REST endpoints (enabled via `show_in_rest`), WordPress auto-applies capability checks -- but only if `map_meta_cap` is set to `true` (see Pitfall 3)
- Rate-limit custom endpoints or rely on WordPress's built-in rate limiting
- Never use `'permission_callback' => '__return_true'` for write operations

**Detection:** Grep for `__return_true` in any `register_rest_route` call. Test endpoints with `curl` without authentication headers.

**Phase mapping:** Phase 3 (REST API) -- must be the first thing implemented in every endpoint.

---

### Pitfall 3: CPT Capability Mapping Without `map_meta_cap`

**What goes wrong:** The CPT is registered with `'capability_type' => 'community_project'` but `'map_meta_cap'` is left at its default `false`. WordPress cannot map meta capabilities (like `edit_post`) to primitive capabilities (like `edit_community_projects`). Result: no user can edit or delete individual projects, or worse, capability checks silently pass/fail in unexpected ways.

**Why it happens:** `map_meta_cap` defaults to `false`, which is the wrong default for nearly every plugin. Developers set a custom `capability_type` (correct instinct for security isolation) but miss this companion flag.

**Consequences:** Editors/admins get "You are not allowed to edit this post" errors. Or in the worst case, permission checks don't work at all and the REST API becomes wide open because the capability being checked doesn't resolve to anything meaningful.

**Prevention:**
```php
register_post_type('community_project', [
    'capability_type' => 'community_project',
    'map_meta_cap'    => true,  // CRITICAL: must be true
    // ... other args
]);
```
- Use `'capability_type' => 'post'` if you do not need isolated capabilities (simpler, less risk)
- If using custom capabilities, add them to the administrator role on plugin activation:
  ```php
  $role = get_role('administrator');
  $role->add_cap('edit_community_projects');
  $role->add_cap('delete_community_projects');
  // etc.
  ```
- Remove capabilities on plugin deactivation/uninstall

**Detection:** Register the CPT, then try editing a post as admin. If you get permission errors, `map_meta_cap` is missing. Check with `current_user_can('edit_community_project', $post_id)`.

**Phase mapping:** Phase 1 (CPT Registration) -- must be correct from initial registration.

---

### Pitfall 4: Missing Output Escaping (XSS)

**What goes wrong:** User-supplied data (project name, description, GitHub URL, one-line installer) is echoed in templates without escaping. XSS is the #1 WordPress vulnerability type at 53.3% of all CVEs.

**Why it happens:** Developers sanitize on input (correct) but assume that means output is safe (wrong). Or they escape when building a variable but not at the point of echo ("escape late" principle violated).

**Consequences:** Stored XSS. If an attacker (or a compromised API client) stores malicious JavaScript in a project's description field, it executes in every visitor's browser on the community page.

**Prevention:**
- Escape at the point of output, every time:
  - HTML context: `esc_html($project_name)`
  - Attribute context: `esc_attr($value)`
  - URL context: `esc_url($github_link)`
  - JavaScript context: `esc_js($value)` or `wp_json_encode()`
- The one-line installer field is especially dangerous -- it contains shell commands. Display in a `<code>` block with `esc_html()`, never render as HTML
- Never use `echo $variable` -- always `echo esc_html($variable)`
- Double-escaping is also a bug: escape once, at the latest possible moment

**Detection:** Grep for `echo $` without an adjacent `esc_` function. Any template outputting meta values without escaping.

**Phase mapping:** Phase 2 (Frontend Display) -- every template line must escape.

---

### Pitfall 5: Not Flushing Rewrite Rules Correctly

**What goes wrong:** The CPT registers a custom URL slug (e.g., `/community-project/my-project/`), but rewrite rules are never flushed. All CPT URLs return 404. Or worse: `flush_rewrite_rules()` is called on every page load via `init` hook, destroying site performance.

**Why it happens:** Flushing is expensive (writes to database), so WordPress does not do it automatically when a CPT is registered. Developers either forget to flush entirely, or flush on every request out of frustration during debugging.

**Consequences:**
- No flush: All custom post type pages return 404. Users/testers think the plugin is broken.
- Flush on every load: Significant performance degradation. The rewrite rules table is rebuilt on every single request.

**Prevention:**
```php
// On plugin activation ONLY:
register_activation_hook(__FILE__, function() {
    // Register CPT first so its rules exist
    community_master_register_cpt();
    flush_rewrite_rules();
});

// On plugin deactivation:
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
```
- Never call `flush_rewrite_rules()` inside the `init` hook
- If the CPT slug changes in an update, flush in the upgrade routine

**Detection:** After activating the plugin, visit a CPT single page. If 404, rewrite rules were not flushed. Check for `flush_rewrite_rules` in `init` or `plugins_loaded` hooks (should not be there).

**Phase mapping:** Phase 1 (CPT Registration) -- activation hook setup.

---

## Moderate Pitfalls

### Pitfall 6: Input Sanitization Gaps on Meta Fields

**What goes wrong:** Meta field values (GitHub URL, installer command, description) are saved to the database without proper sanitization. Malicious content gets stored even if it is escaped on output.

**Prevention:**
- Sanitize on save, escape on output -- both are required:
  ```php
  // Saving:
  update_post_meta($post_id, '_github_url', esc_url_raw($url));
  update_post_meta($post_id, '_description', sanitize_textarea_field($desc));
  update_post_meta($post_id, '_installer_cmd', sanitize_text_field($cmd));
  ```
- Use `esc_url_raw()` (not `esc_url()`) for saving URLs -- it preserves the raw URL for database storage
- Use `sanitize_text_field()` for single-line strings
- Use `sanitize_textarea_field()` for multi-line content
- Use `wp_kses_post()` only if you deliberately want to allow some HTML

**Detection:** Check every `update_post_meta` call. If the second argument to `update_post_meta` is an unsanitized `$_POST` value, it is a vulnerability.

**Phase mapping:** Phase 1 (Meta Box / CPT) and Phase 3 (REST API input handling).

---

### Pitfall 7: Postmeta Performance at Scale (Not a Problem Here, But a Design Trap)

**What goes wrong:** Developers preemptively create custom database tables "for performance" when postmeta would work fine. Or they use postmeta for data that genuinely needs custom tables and face slow queries later.

**Prevention:**
- Community Master has ~10-20 projects with 5-6 meta fields each. This is trivially small. Use `wp_postmeta` -- period.
- Custom tables are warranted only at 50k+ posts with 10+ meta fields per post, or when you need complex JOINs/aggregations
- Postmeta gives you: free REST API integration, free admin search, free WordPress caching, free backup/migration compatibility
- Do NOT prematurely optimize with custom tables. The overhead of maintaining schema migrations, custom CRUD functions, and losing WordPress integration is not worth it for this scale.

**Detection:** If someone suggests custom tables for this plugin, ask "how many records?" The answer (~50 max) makes it a non-issue.

**Phase mapping:** Phase 1 (Architecture Decision) -- decide once, use postmeta.

---

### Pitfall 8: Enqueuing Scripts/Styles Incorrectly

**What goes wrong:** Plugin CSS/JS is loaded globally on every page (including admin pages where it is not needed), causing conflicts with the theme or other plugins. Or worse, scripts are loaded via direct `<script>` tags in templates instead of `wp_enqueue_script`.

**Prevention:**
- Always use `wp_enqueue_script()` and `wp_enqueue_style()` via proper hooks
- Load frontend assets only on pages where the shortcode/block is rendered:
  ```php
  // Only enqueue on frontend when shortcode is present
  add_action('wp_enqueue_scripts', function() {
      if (is_singular('community_project') || has_shortcode(get_post()->post_content ?? '', 'community_master')) {
          wp_enqueue_style('community-master-grid', plugins_url('css/grid.css', __FILE__));
      }
  });
  ```
- Load admin assets only on the plugin's own admin pages (check `$hook_suffix`)
- Use unique, prefixed handles: `'community-master-grid'` not `'grid'` or `'style'`
- Declare jQuery as a dependency if using it, do not bundle your own copy
- Use `wp_enqueue_script` with `['strategy' => 'defer']` (WP 6.3+) for non-critical scripts

**Detection:** Load any page on the site and check if plugin CSS/JS appears in the source when the shortcode is not present. If yes, assets are loading globally.

**Phase mapping:** Phase 2 (Frontend Display) -- asset loading strategy.

---

### Pitfall 9: Block Editor Registration Without `block.json`

**What goes wrong:** The Gutenberg block is registered using legacy PHP-only `register_block_type()` without a `block.json` manifest. This works but misses out on automatic asset enqueuing, block discovery, and forward compatibility with WordPress block tooling.

**Prevention:**
- Always use `block.json` as the canonical block definition (WordPress 5.8+):
  ```json
  {
      "apiVersion": 3,
      "name": "community-master/project-grid",
      "title": "Community Projects Grid",
      "category": "widgets",
      "attributes": {},
      "supports": { "html": false },
      "editorScript": "file:./index.js",
      "style": "file:./style.css",
      "render": "file:./render.php"
  }
  ```
- Register via: `register_block_type(__DIR__ . '/blocks/project-grid')`
- For dynamic blocks (server-rendered), use `render` in block.json (WP 6.1+) or `render_callback` in PHP
- The `save` function in JS should return `null` for server-rendered blocks
- Set `show_in_rest => true` on the CPT, otherwise the block editor cannot load posts

**Detection:** Block does not appear in the block inserter. Or block editor shows "This block has encountered an error" -- usually means the JS `save()` output does not match what was stored.

**Phase mapping:** Phase 2 (Frontend Display) -- if implementing Gutenberg block. Shortcode-first is simpler and equally functional for this use case.

---

### Pitfall 10: No Database Version Tracking for Updates

**What goes wrong:** Plugin v2 needs a new meta field or changed data structure, but there is no migration mechanism. Existing installations break or have stale data after update.

**Prevention:**
- Store a schema version in the options table from day one:
  ```php
  define('COMMUNITY_MASTER_DB_VERSION', '1.0');

  function community_master_check_version() {
      $installed = get_option('community_master_db_version', '0');
      if (version_compare($installed, COMMUNITY_MASTER_DB_VERSION, '<')) {
          community_master_upgrade($installed);
          update_option('community_master_db_version', COMMUNITY_MASTER_DB_VERSION);
      }
  }
  add_action('plugins_loaded', 'community_master_check_version');
  ```
- Make migrations idempotent -- safe to run multiple times
- Never assume meta fields exist. Always use `get_post_meta()` with a fallback default
- For this plugin's scale, migrations will be simple option/meta additions, not table alterations

**Detection:** Deploy an update and check if old posts still display correctly. If new fields show empty/broken without migration, version tracking is missing.

**Phase mapping:** Phase 1 (Foundation) -- set up version tracking infrastructure even before there is anything to migrate.

---

### Pitfall 11: REST API Endpoint Registration Timing

**What goes wrong:** Custom REST routes are registered on the wrong hook, or the CPT's built-in REST endpoints are not available because `show_in_rest` was not set during registration.

**Prevention:**
- Register custom REST routes on `rest_api_init`, not `init`:
  ```php
  add_action('rest_api_init', function() {
      register_rest_route('community-master/v1', '/projects', [...]);
  });
  ```
- For the CPT's built-in REST controller, these args must be set in `register_post_type`:
  ```php
  'show_in_rest'    => true,
  'rest_base'       => 'community-projects',
  'rest_namespace'  => 'wp/v2',  // or custom namespace
  ```
- Note: `show_in_rest => true` is also required for Gutenberg editor support. Without it, the CPT falls back to the Classic Editor.
- Use the built-in CPT REST endpoints for standard CRUD. Only register custom routes for specialized operations (e.g., bulk import).

**Detection:** Try `GET /wp-json/wp/v2/community-projects` -- if 404, `show_in_rest` is missing or `rest_base` is wrong.

**Phase mapping:** Phase 1 (CPT Registration) and Phase 3 (REST API).

---

## Minor Pitfalls

### Pitfall 12: Forgetting `uninstall.php` / Cleanup

**What goes wrong:** Plugin is deactivated and deleted but leaves orphaned postmeta, options, and CPT posts in the database. Accumulates database bloat over time.

**Prevention:**
- Create `uninstall.php` in the plugin root (preferred over `register_uninstall_hook`):
  ```php
  <?php
  if (!defined('WP_UNINSTALL_PLUGIN')) exit;
  // Delete all community project posts
  $posts = get_posts(['post_type' => 'community_project', 'numberposts' => -1, 'post_status' => 'any']);
  foreach ($posts as $post) wp_delete_post($post->ID, true);
  // Delete plugin options
  delete_option('community_master_db_version');
  ```
- Deactivation hook: only flush rewrite rules, do NOT delete data (user might reactivate)
- Uninstall: delete everything (user chose to remove the plugin)

**Phase mapping:** Phase 1 (Plugin Structure) -- create the file early even if it is minimal.

---

### Pitfall 13: Hardcoded URLs and Paths

**What goes wrong:** Plugin uses hardcoded paths like `/wp-content/plugins/community-master/` instead of WordPress functions. Breaks on any non-standard installation (custom `wp-content` directory, symlinked plugins, Bedrock-style structures).

**Prevention:**
- Always use:
  - `plugin_dir_path(__FILE__)` for filesystem paths
  - `plugins_url('file.css', __FILE__)` for URLs
  - `rest_url('community-master/v1/projects')` for API URLs
  - `home_url('/community-master/')` for frontend URLs
- Never assume `wp-content` is at `/wp-content/`

**Phase mapping:** All phases -- use WordPress path functions everywhere.

---

### Pitfall 14: Translation-Readiness Omission

**What goes wrong:** All strings are hardcoded in German. If the plugin is ever used on a multilingual site or shared publicly, it cannot be translated.

**Prevention:**
- Wrap all user-facing strings in `__()` or `_e()` from the start:
  ```php
  __('Community Projects', 'community-master')
  ```
- Create a text domain matching the plugin slug: `community-master`
- Set `'Text Domain': 'community-master'` in plugin header
- Even for a single-site German plugin, this costs nothing and avoids a painful retrofit later

**Phase mapping:** Phase 1 (Foundation) -- use translation functions from the first string.

---

### Pitfall 15: Media Upload Attachment Orphaning

**What goes wrong:** Project logos are uploaded via the Media Library but are not properly attached to the community project post. When the project is deleted, the logo image remains as an orphaned attachment.

**Prevention:**
- When saving the logo, attach it to the post:
  ```php
  wp_update_post(['ID' => $attachment_id, 'post_parent' => $post_id]);
  ```
- Store the attachment ID (not the URL) in postmeta: `update_post_meta($post_id, '_logo_id', $attachment_id)`
- Use `wp_get_attachment_image()` for display -- this gives you responsive `srcset` for free
- In `uninstall.php`, delete attachments with the posts

**Phase mapping:** Phase 1 (Meta Fields) -- logo field implementation.

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| CPT Registration | Pitfall 3 (map_meta_cap), Pitfall 5 (rewrite flush) | Set `map_meta_cap => true`, flush only on activation |
| Meta Fields | Pitfall 6 (sanitization gaps) | Sanitize every `update_post_meta` call |
| Frontend Grid | Pitfall 4 (XSS), Pitfall 8 (global CSS) | Escape all output, conditional enqueue |
| REST API | Pitfall 2 (open endpoints), Pitfall 11 (timing) | Capability checks on every route, use `rest_api_init` |
| Block Editor | Pitfall 9 (no block.json) | Use block.json, or stick with shortcode for simplicity |
| Plugin Updates | Pitfall 10 (no versioning) | Version tracking from Phase 1 |
| Deployment | Pitfall 13 (hardcoded paths) | WordPress path functions only |

## Recommendation for Community Master

Given the project scope (~20 projects, single site, API access for automation), the highest-impact pitfalls to address are:

1. **REST API security (Pitfall 2)** -- this is the primary attack surface since Claude will write to the API programmatically with Application Passwords
2. **Output escaping (Pitfall 4)** -- the one-line installer field displaying shell commands is a tempting XSS vector
3. **CPT capability mapping (Pitfall 3)** -- get this wrong and either admins cannot edit projects or the REST API is wide open
4. **Rewrite flush timing (Pitfall 5)** -- the most common "it does not work" bug in new plugins

The postmeta vs custom tables debate (Pitfall 7) is a non-issue at this scale. Use postmeta and move on.

## Sources

- [WordPress Plugin Security Handbook](https://developer.wordpress.org/plugins/security/)
- [WordPress Nonces Documentation](https://developer.wordpress.org/apis/security/nonces/)
- [register_post_type() Reference](https://developer.wordpress.org/reference/functions/register_post_type/)
- [flush_rewrite_rules() Reference](https://developer.wordpress.org/reference/functions/flush_rewrite_rules/)
- [Meta capabilities for custom post types (Justin Tadlock)](https://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types)
- [Creating dynamic blocks (Block Editor Handbook)](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/)
- [Patchstack State of WordPress Security 2025](https://patchstack.com/whitepaper/state-of-wordpress-security-in-2025/)
- [Top 12 WordPress Plugin Vulnerabilities 2025](https://www.siteguarding.com/security-blog/top-12-wordpress-plugin-vulnerabilities-of-2025-how-to-detect-and-fix-them/)
- [Scaling WordPress - Custom Tables vs Postmeta](https://sarathlal.com/scaling-wordpress-custom-tables-postmeta-bottleneck/)
- [WordPress Plugin Database Migrations](https://www.voxfor.com/how-to-handling-database-migrations-in-wordpress-plugins/)
- [WordPress Plugin Common Issues](https://developer.wordpress.org/plugins/wordpress-org/common-issues/)
- [WordPress Plugin Security Best Practices 2026](https://xtnd.net/blog/wordpress-plugin-security-best-practices/)
