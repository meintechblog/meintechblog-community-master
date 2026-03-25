# Phase 1: Plugin Core & Admin - Research

**Researched:** 2026-03-24
**Domain:** WordPress Plugin Development (Custom Post Type, Meta Fields, Admin UI)
**Confidence:** HIGH

## Summary

Phase 1 establishes the WordPress plugin foundation: CPT registration, meta fields, admin meta boxes, capability management, and lifecycle hooks (activation/deactivation/uninstall). The technology stack is 100% WordPress core APIs -- no external PHP or JS dependencies are needed for this phase.

The critical implementation details are: (1) `map_meta_cap => true` with custom `capability_type` requires explicitly granting all generated capabilities to Administrator and Editor roles on activation, (2) description is a textarea meta field (not post content), so the CPT `supports` array must NOT include `'editor'`, (3) meta field sanitization callbacks must be defined both in `register_post_meta` (for REST) and in the meta box save handler (for admin form saves).

**Primary recommendation:** Build in strict order -- plugin bootstrap, CPT registration with capabilities, meta field registration, meta box UI, admin columns, lifecycle hooks. Each step is testable in isolation.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Project description is a simple textarea meta field, NOT the WordPress post content/editor. Keeps the plugin lean -- no Gutenberg editor complexity needed for short project descriptions.
- **D-02:** Use custom capability_type `community_project` with `map_meta_cap => true`. Both Admins and Editors can manage projects. Custom capabilities must be added on plugin activation and removed on uninstall.
- **D-03:** The CPT list table in wp-admin shows custom columns: Logo Thumbnail, GitHub URL, and Sortierung (menu_order value). These give quick overview without opening each project.
- **D-04:** Plugin name: "Community Master". Text domain: `community-master`. Menu label: "Community Master". Plugin slug/folder: `meintechblog-community-master`.

### Claude's Discretion
- Meta box layout and grouping (single meta box vs. multiple)
- Admin CSS for meta boxes (minimal, functional styling)
- Exact sanitize/escape functions per field type
- Activation/deactivation hook implementation details

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| FOUND-01 | Plugin registriert Custom Post Type "community_project" mit Admin-UI | CPT registration pattern with `register_post_type()`, `show_ui => true`, custom capability_type |
| FOUND-02 | Plugin aktiviert Featured Image Support fuer Projekt-Logos | `'supports' => ['title', 'thumbnail']` in CPT registration |
| FOUND-03 | Plugin flusht Rewrite Rules nur bei Activation/Deactivation | `register_activation_hook` / `register_deactivation_hook` with `flush_rewrite_rules()` |
| FOUND-04 | Plugin hat saubere Uninstall-Routine (entfernt CPT-Daten und Optionen) | `uninstall.php` pattern with `WP_UNINSTALL_PLUGIN` check, deletes posts + caps + options |
| FIELD-01 | Admin kann Projekt-Name eingeben (= Post Title) | CPT `'supports' => ['title']` -- uses native post title |
| FIELD-02 | Admin kann Projekt-Beschreibung eingeben (= Meta) | `register_post_meta` with `_community_master_description`, textarea in meta box |
| FIELD-03 | Admin kann Projekt-Logo hochladen (= Featured Image) | CPT `'supports' => ['thumbnail']` -- native featured image picker |
| FIELD-04 | Admin kann GitHub-URL eingeben (Meta Field, validiert auf github.com) | `register_post_meta` with `sanitize_callback => 'esc_url_raw'`, custom validation for `github.com` domain |
| FIELD-05 | Admin kann optionalen One-Line-Installer eingeben (Meta Field) | `register_post_meta` with `sanitize_callback => 'sanitize_text_field'` |
| FIELD-06 | Admin kann Projektreihenfolge festlegen (menu_order) | CPT `'supports' => ['page-attributes']` enables native menu_order field, or use meta box |
| SEC-01 | Alle Meta Field Eingaben werden sanitized (sanitize_callback) | Per-field sanitization: `esc_url_raw` for URLs, `sanitize_textarea_field` for description, `sanitize_text_field` for installer |
| SEC-04 | Meta Boxes verwenden Nonce-Verification | `wp_nonce_field()` in render, `wp_verify_nonce()` in save handler |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| WordPress | 6.6+ | Host platform | Stable CPT + REST API. Current line. |
| PHP | 8.2+ | Runtime | WordPress 6.7+ recommends 8.3. Typed properties, readonly, enums available. |
| `register_post_type()` | WP Core | CPT registration | Native, zero-dependency, gives admin UI + REST for free |
| `register_post_meta()` | WP Core | Meta field registration | Defines sanitize/auth callbacks, REST exposure in one place |
| `add_meta_box()` | WP Core | Admin UI for custom fields | Renders field inputs on edit screen |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `wp_nonce_field` / `wp_verify_nonce` | WP Core | CSRF protection | Every meta box form + save handler |
| `esc_url_raw()` | WP Core | URL sanitization for DB | When saving URL fields to postmeta |
| `sanitize_textarea_field()` | WP Core | Multiline text sanitization | When saving description field |
| `sanitize_text_field()` | WP Core | Single-line text sanitization | When saving installer command field |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Native `register_post_meta` | ACF / Meta Box plugin | Adds plugin dependency for only 3-4 fields. Not justified. |
| `add_meta_box` for all fields | Gutenberg sidebar panel | Requires JS build toolchain. Decision D-01 explicitly avoids this. |
| Custom `menu_order` meta | `'supports' => ['page-attributes']` | page-attributes adds parent dropdown too. Custom meta box is cleaner for order-only. |

**Installation:**
```bash
# No dependencies needed for Phase 1. Pure PHP plugin using WordPress core APIs.
# Optional dev tooling:
composer require --dev wp-coding-standards/wpcs:"^3.0" squizlabs/php_codesniffer:"^3.9"
```

## Architecture Patterns

### Recommended Project Structure
```
meintechblog-community-master/
  community-master.php          # Plugin header, constants, require_once, activation/deactivation hooks
  uninstall.php                 # Cleanup on plugin deletion
  includes/
    class-community-master.php  # Singleton orchestrator: wires all components
    class-cpt-project.php       # CPT registration + meta field registration
    class-meta-boxes.php        # Meta box rendering + save handler
    class-admin-columns.php     # Custom columns for CPT list table
```

### Pattern 1: CPT Registration with Custom Capabilities
**What:** Register `community_project` CPT with isolated capabilities mapped via `map_meta_cap`.
**When to use:** Always -- this is the Phase 1 foundation.
**Example:**
```php
// Source: WordPress Developer Reference - register_post_type()
register_post_type('community_project', [
    'labels' => [
        'name'               => __('Community Projects', 'community-master'),
        'singular_name'      => __('Community Project', 'community-master'),
        'add_new'            => __('Add New', 'community-master'),
        'add_new_item'       => __('Add New Project', 'community-master'),
        'edit_item'          => __('Edit Project', 'community-master'),
        'new_item'           => __('New Project', 'community-master'),
        'view_item'          => __('View Project', 'community-master'),
        'search_items'       => __('Search Projects', 'community-master'),
        'not_found'          => __('No projects found', 'community-master'),
        'not_found_in_trash' => __('No projects found in Trash', 'community-master'),
        'menu_name'          => __('Community Master', 'community-master'),
    ],
    'public'            => false,
    'show_ui'           => true,
    'show_in_menu'      => true,
    'show_in_rest'      => true,       // Required for future REST API phase
    'capability_type'   => 'community_project',
    'map_meta_cap'      => true,       // CRITICAL: maps edit_post -> edit_community_project per-post
    'supports'          => ['title', 'thumbnail'],  // NO 'editor' per D-01
    'menu_icon'         => 'dashicons-groups',
    'menu_position'     => 25,
    'has_archive'       => false,
]);
```

### Pattern 2: Full Capability List for Custom capability_type
**What:** When `capability_type => 'community_project'` and `map_meta_cap => true`, WordPress generates these capabilities that MUST be granted to roles.
**Example:**
```php
// Source: WordPress Developer Reference - register_post_type() capabilities section
// These primitive caps must be added to Administrator and Editor roles on activation:
$caps = [
    'edit_community_projects',
    'edit_others_community_projects',
    'publish_community_projects',
    'read_private_community_projects',
    'delete_community_projects',
    'delete_private_community_projects',
    'delete_published_community_projects',
    'delete_others_community_projects',
    'edit_private_community_projects',
    'edit_published_community_projects',
    'create_community_projects',  // maps to edit_community_projects by default
];

// Meta capabilities (auto-mapped, do NOT add to roles):
// edit_community_project, read_community_project, delete_community_project
```

### Pattern 3: Meta Box Save with Nonce + Sanitization
**What:** The save handler for meta box data, combining nonce verification and field-specific sanitization.
**Example:**
```php
// Source: WordPress Plugin Handbook - Custom Meta Boxes
public function save_meta(int $post_id): void {
    // 1. Verify nonce
    $nonce = isset($_POST['community_master_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['community_master_nonce']))
        : '';
    if (!wp_verify_nonce($nonce, 'community_master_save_meta')) {
        return;
    }

    // 2. Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // 3. Check permissions
    if (!current_user_can('edit_community_project', $post_id)) {
        return;
    }

    // 4. Sanitize and save each field
    if (isset($_POST['_community_master_github_url'])) {
        $url = esc_url_raw(wp_unslash($_POST['_community_master_github_url']));
        // Validate github.com domain
        if ($url !== '' && !str_starts_with($url, 'https://github.com/')) {
            $url = '';  // Reject non-GitHub URLs
        }
        update_post_meta($post_id, '_community_master_github_url', $url);
    }

    if (isset($_POST['_community_master_description'])) {
        $desc = sanitize_textarea_field(wp_unslash($_POST['_community_master_description']));
        update_post_meta($post_id, '_community_master_description', $desc);
    }

    if (isset($_POST['_community_master_installer'])) {
        $installer = sanitize_text_field(wp_unslash($_POST['_community_master_installer']));
        update_post_meta($post_id, '_community_master_installer', $installer);
    }
}
```

### Pattern 4: Custom Admin Columns
**What:** Add Logo Thumbnail, GitHub URL, and Sortierung columns to the CPT list table.
**Example:**
```php
// Source: WordPress Plugin Handbook - Custom Columns
// Register columns
add_filter('manage_community_project_posts_columns', function(array $columns): array {
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['cm_logo']      = __('Logo', 'community-master');
            $new['cm_github']    = __('GitHub URL', 'community-master');
            $new['cm_sort']      = __('Sortierung', 'community-master');
        }
    }
    // Remove date column if desired
    return $new;
});

// Render column content
add_action('manage_community_project_posts_custom_column', function(string $column, int $post_id): void {
    switch ($column) {
        case 'cm_logo':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, [40, 40]);
            } else {
                echo '&mdash;';
            }
            break;
        case 'cm_github':
            $url = get_post_meta($post_id, '_community_master_github_url', true);
            echo $url ? '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>' : '&mdash;';
            break;
        case 'cm_sort':
            $post = get_post($post_id);
            echo esc_html($post->menu_order);
            break;
    }
}, 10, 2);

// Make Sortierung column sortable
add_filter('manage_edit-community_project_sortable_columns', function(array $columns): array {
    $columns['cm_sort'] = 'menu_order';
    return $columns;
});
```

### Anti-Patterns to Avoid
- **Using `'editor'` in supports array:** Decision D-01 explicitly uses a meta field for description, not post content. Including `'editor'` loads Gutenberg/Classic Editor unnecessarily.
- **Flushing rewrite rules on `init`:** Destroys performance. Flush ONLY in activation/deactivation hooks.
- **Direct `$_POST` access without sanitization:** Always `wp_unslash()` then sanitize, then verify nonce BEFORE processing any data.
- **Forgetting `wp_unslash()` before sanitization:** WordPress adds slashes to `$_POST` data. Must unslash first, then sanitize.
- **Granting meta capabilities to roles:** Only grant primitive capabilities (`edit_community_projects`), never meta capabilities (`edit_community_project` singular). WordPress maps meta to primitive automatically when `map_meta_cap => true`.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Media upload for logos | Custom file upload handler | WordPress Featured Image (`'supports' => ['thumbnail']`) | Handles upload, crop, responsive srcset, attachment lifecycle |
| Project ordering | Custom sort order meta field with drag-and-drop | WordPress `menu_order` via `'supports' => ['page-attributes']` or simple number input in meta box | Built-in, sortable, used by `orderby => 'menu_order'` in queries |
| CSRF protection | Custom token system | `wp_nonce_field()` / `wp_verify_nonce()` | Battle-tested, WordPress standard, handles expiry |
| Capability system | Manual `current_user_can('manage_options')` checks | Custom `capability_type` with `map_meta_cap => true` | Per-post permission checks, role-based, extensible |

**Key insight:** Every feature in Phase 1 has a WordPress core API. Zero external dependencies. The risk is not "what library to use" but "using the WordPress API correctly" (especially capabilities and sanitization).

## Common Pitfalls

### Pitfall 1: Missing map_meta_cap
**What goes wrong:** CPT registered with `'capability_type' => 'community_project'` but `map_meta_cap` left at default `false`. No user can edit individual projects because WordPress cannot resolve per-post capabilities.
**Why it happens:** `map_meta_cap` defaults to `false` -- the wrong default for custom capability types.
**How to avoid:** Always set `'map_meta_cap' => true` when using a custom capability_type.
**Warning signs:** "You are not allowed to edit this post" errors for administrators.

### Pitfall 2: Incomplete Capability Grant on Activation
**What goes wrong:** Only a few capabilities are granted (e.g., `edit_community_projects`) but not all 11 primitive capabilities. Result: admins can create but not delete, or can edit own but not others.
**Why it happens:** Developers grant 2-3 obvious capabilities and miss the full set WordPress generates.
**How to avoid:** Grant ALL 11 primitive capabilities to both Administrator and Editor roles on activation. Remove them all on uninstall.
**Warning signs:** Partial functionality -- some admin actions work, others silently fail.

### Pitfall 3: Double Sanitization in register_post_meta + Meta Box Save
**What goes wrong:** `sanitize_callback` is defined in `register_post_meta` (for REST API saves) but the meta box save handler also directly calls `update_post_meta`. If the save handler does not sanitize, admin form saves bypass the REST sanitization. If it over-sanitizes, data may be corrupted.
**Why it happens:** `register_post_meta`'s `sanitize_callback` only fires through the REST API and `update_metadata()` calls. The meta box save handler calls `update_post_meta()` directly, which DOES trigger `sanitize_meta()` (and thus the registered callback). However, relying only on this is fragile -- explicitly sanitize in the save handler too.
**How to avoid:** Sanitize explicitly in the meta box save handler AND set `sanitize_callback` in `register_post_meta`. Both paths should produce identical results.

### Pitfall 4: Forgetting wp_unslash Before Sanitization
**What goes wrong:** WordPress adds magic quotes/slashes to all `$_POST`/`$_GET`/`$_REQUEST` data. If you sanitize without unslashing first, backslashes accumulate on repeated saves (the "addslashes" problem).
**Why it happens:** PHP itself removed magic quotes in 5.4, but WordPress re-adds them for backward compatibility.
**How to avoid:** Always `wp_unslash($_POST['field'])` before passing to any `sanitize_*` function.
**Warning signs:** Backslashes appearing in saved data that multiply each time the post is saved.

### Pitfall 5: menu_order Not Available Without 'page-attributes' Support
**What goes wrong:** Developer expects `menu_order` to be writable but the CPT does not include `'page-attributes'` in supports. WordPress does not expose the Order field in the admin UI.
**Why it happens:** `menu_order` is a core `wp_posts` column, so it exists in the DB, but the admin UI for it only appears with `'page-attributes'` support -- which also adds an unwanted "Parent" dropdown.
**How to avoid:** Either add `'page-attributes'` to supports (simplest), or create a custom meta box that directly sets `$post->menu_order` via `wp_update_post()`. The custom meta box approach is cleaner since we only want the order number, not the parent dropdown.
**Warning signs:** No "Order" field visible in the edit screen.

## Code Examples

### Plugin Header and Bootstrap
```php
<?php
/**
 * Plugin Name: Community Master
 * Description: Manage and display community projects on meintechblog.de
 * Version:     1.0.0
 * Author:      meintechblog
 * Text Domain: community-master
 * Requires PHP: 8.2
 * Requires at least: 6.6
 */

defined('ABSPATH') || exit;

define('COMMUNITY_MASTER_VERSION', '1.0.0');
define('COMMUNITY_MASTER_FILE', __FILE__);
define('COMMUNITY_MASTER_DIR', plugin_dir_path(__FILE__));

require_once COMMUNITY_MASTER_DIR . 'includes/class-community-master.php';
require_once COMMUNITY_MASTER_DIR . 'includes/class-cpt-project.php';
require_once COMMUNITY_MASTER_DIR . 'includes/class-meta-boxes.php';
require_once COMMUNITY_MASTER_DIR . 'includes/class-admin-columns.php';

Community_Master::instance();

// Activation
register_activation_hook(__FILE__, function(): void {
    CM_CPT_Project::register();
    CM_CPT_Project::add_capabilities();
    flush_rewrite_rules();
    update_option('community_master_version', COMMUNITY_MASTER_VERSION);
});

// Deactivation
register_deactivation_hook(__FILE__, function(): void {
    flush_rewrite_rules();
});
```

### Activation: Grant All Capabilities
```php
// Source: WordPress Developer Reference - Custom Post Types and Capabilities
public static function add_capabilities(): void {
    $caps = [
        'edit_community_projects',
        'edit_others_community_projects',
        'publish_community_projects',
        'read_private_community_projects',
        'delete_community_projects',
        'delete_private_community_projects',
        'delete_published_community_projects',
        'delete_others_community_projects',
        'edit_private_community_projects',
        'edit_published_community_projects',
        'create_community_projects',
    ];

    foreach (['administrator', 'editor'] as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }
}
```

### Uninstall.php
```php
<?php
// Source: WordPress Plugin Handbook - Uninstall Methods
defined('WP_UNINSTALL_PLUGIN') || exit;

// Delete all community project posts (including trashed)
$posts = get_posts([
    'post_type'   => 'community_project',
    'numberposts' => -1,
    'post_status' => 'any',
]);
foreach ($posts as $post) {
    wp_delete_post($post->ID, true);  // Force delete, skip trash
}

// Remove capabilities from roles
$caps = [
    'edit_community_projects',
    'edit_others_community_projects',
    'publish_community_projects',
    'read_private_community_projects',
    'delete_community_projects',
    'delete_private_community_projects',
    'delete_published_community_projects',
    'delete_others_community_projects',
    'edit_private_community_projects',
    'edit_published_community_projects',
    'create_community_projects',
];
foreach (['administrator', 'editor'] as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        foreach ($caps as $cap) {
            $role->remove_cap($cap);
        }
    }
}

// Delete plugin options
delete_option('community_master_version');
```

### Meta Field Registration with Sanitize Callbacks
```php
// Source: WordPress Developer Reference - register_post_meta()
public function register_meta_fields(): void {
    register_post_meta('community_project', '_community_master_description', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback'     => fn() => current_user_can('edit_community_projects'),
    ]);

    register_post_meta('community_project', '_community_master_github_url', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'auth_callback'     => fn() => current_user_can('edit_community_projects'),
    ]);

    register_post_meta('community_project', '_community_master_installer', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => fn() => current_user_can('edit_community_projects'),
    ]);
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `add_meta_box` only | `register_post_meta` + `add_meta_box` | WordPress 4.9.8+ | Meta registration provides REST exposure + sanitize callbacks. Meta boxes provide admin UI. Use both. |
| `register_post_type` without `show_in_rest` | Always set `show_in_rest => true` | WordPress 5.0+ (Gutenberg) | Without it, CPT falls back to Classic Editor. Required for block editor and REST API. |
| Manual capability checks per endpoint | `capability_type` + `map_meta_cap` | WordPress 3.0+ (stable) | WordPress auto-maps per-post permissions. No manual mapping needed. |
| `sanitize_text_field` for URLs | `esc_url_raw` for saving, `esc_url` for output | Long-standing | `esc_url_raw` preserves raw URL for DB. `esc_url` adds protocol for output context. |

## Open Questions

1. **menu_order Implementation: page-attributes vs. Custom Meta Box**
   - What we know: `'page-attributes'` support adds both Order and Parent fields. We only want Order.
   - What's unclear: Whether the Parent dropdown causes confusion for editors.
   - Recommendation: Use a custom meta box with a simple number input that sets `menu_order` via `wp_update_post()`. Cleaner UX, no unwanted Parent field.

2. **GitHub URL Validation Strictness**
   - What we know: Context specifies URLs must start with `https://github.com/`. `esc_url_raw` alone does not enforce domain.
   - What's unclear: Should we validate the full pattern (e.g., `https://github.com/org/repo`)?
   - Recommendation: Validate prefix `https://github.com/` only. Don't over-validate path structure since GitHub URLs vary (orgs, repos, monorepo paths).

## Project Constraints (from CLAUDE.md)

- WordPress plugin (PHP), compatible with current WordPress 6.x
- Plugin deployed on meintechblog.de
- Must integrate with existing WordPress theme (no custom theme/styling for admin)
- GitHub push only via SSH, not HTTPS
- WordPress REST API for programmatic management (future phases)
- GSD workflow enforcement: use GSD commands for file changes

## Sources

### Primary (HIGH confidence)
- [register_post_type() Reference](https://developer.wordpress.org/reference/functions/register_post_type/) - CPT args, capability_type, map_meta_cap
- [Custom Post Types and Capabilities](https://learn.wordpress.org/tutorial/custom-post-types-and-capabilities/) - Full capability list generation
- [Custom Meta Boxes Handbook](https://developer.wordpress.org/plugins/metadata/custom-meta-boxes/) - Meta box rendering + save pattern
- [register_post_meta() / register_meta()](https://developer.wordpress.org/reference/functions/register_meta/) - sanitize_callback, auth_callback, show_in_rest
- [WordPress Plugin Security Handbook](https://developer.wordpress.org/plugins/security/) - Nonce, sanitization, escaping patterns

### Secondary (MEDIUM confidence)
- [Meta capabilities for custom post types (Justin Tadlock)](https://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types) - Detailed capability mapping explanation
- [Custom Capabilities Guide (Voxfor)](https://www.voxfor.com/custom-post-types-guide-for-implementing-custom-capabilities-in-wordpress/) - Role capability grant patterns
- [Sanitization Best Practices (YourWPweb)](https://yourwpweb.com/2025/09/26/how-to-sanitize-and-validate-inputs-with-sanitize_text_field-and-similar-in-wordpress/) - sanitize_text_field vs sanitize_textarea_field

### Project Research (HIGH confidence)
- `.planning/research/ARCHITECTURE.md` - Plugin file structure, CPT design, data flow
- `.planning/research/PITFALLS.md` - 15 pitfalls covering security, capabilities, rewrite rules
- `.planning/research/STACK.md` - PHP 8.2+, WordPress 6.6+, zero external dependencies

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - WordPress core APIs only, well-documented, stable for years
- Architecture: HIGH - Plugin Handbook patterns, validated by prior research
- Pitfalls: HIGH - Based on official security docs + known WordPress CVE patterns

**Research date:** 2026-03-24
**Valid until:** 2026-06-24 (WordPress core APIs are stable; 90-day validity)
