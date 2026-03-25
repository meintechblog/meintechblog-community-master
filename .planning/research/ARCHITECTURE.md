# Architecture Research: Community Master WordPress Plugin

**Domain:** WordPress Plugin (Custom Post Type + REST API + Frontend Display)
**Researched:** 2026-03-24
**Confidence:** HIGH (WordPress plugin patterns are stable and well-documented)

## Recommended Architecture

Class-based, single-responsibility architecture following the WordPress Plugin Handbook conventions. Each concern lives in its own class file, wired together through a central bootstrap in the main plugin file.

```
community-master/
  community-master.php          # Plugin header, bootstrap, activation/deactivation hooks
  uninstall.php                 # Cleanup on plugin deletion (remove CPT data, options)
  includes/
    class-community-master.php  # Main orchestrator: loads dependencies, registers hooks
    class-cpt-project.php       # Custom Post Type registration + meta field registration
    class-meta-boxes.php        # Admin meta box rendering + save handlers
    class-rest-controller.php   # REST API controller (extends WP_REST_Controller)
    class-shortcode.php         # [community_master] shortcode registration + rendering
    class-block.php             # Gutenberg block registration (optional, phase 2)
    class-assets.php            # Enqueue scripts/styles (admin + frontend)
  templates/
    tile-grid.php               # Frontend HTML template for the project grid
    single-tile.php             # Single project tile template partial
  assets/
    css/
      frontend.css              # Minimal frontend styles (grid layout only, inherits theme)
    js/
      copy-installer.js         # Clipboard copy for one-line installer
  languages/
    community-master.pot        # Translation template (future-proofing)
```

### Why This Structure

- **WordPress Plugin Handbook pattern** -- the `/includes`, `/templates`, `/assets` split is the documented standard
- **Class-per-concern** prevents the "god class" anti-pattern while keeping the plugin small enough that a service container is overkill
- **Templates separate from logic** -- rendering HTML in PHP template files, not embedded in class methods
- **No build toolchain** -- pure PHP + vanilla CSS/JS. No webpack, no npm. The plugin is small enough that build complexity adds no value

## Component Boundaries

| Component | Responsibility | Depends On | Depended On By |
|-----------|---------------|------------|----------------|
| `community-master.php` | Plugin header, activation/deactivation hooks, autoload, instantiation | WordPress core | Everything (entry point) |
| `class-community-master.php` | Wire all components together via `add_action`/`add_filter` | All other classes | `community-master.php` |
| `class-cpt-project.php` | Register CPT `community_project`, register post meta fields | WordPress `init` hook | Meta Boxes, REST Controller, Shortcode |
| `class-meta-boxes.php` | Render admin UI for project fields (logo, github link, installer) | CPT registration, `add_meta_box` | WordPress admin |
| `class-rest-controller.php` | CRUD endpoints at `/wp-json/community-master/v1/projects` | CPT, `WP_REST_Controller` | External consumers (Claude, scripts) |
| `class-shortcode.php` | Register `[community_master]` shortcode, load template | CPT (for querying), Templates | WordPress content rendering |
| `class-assets.php` | Enqueue CSS/JS on correct pages only | WordPress `wp_enqueue_scripts` | Shortcode (frontend), Meta Boxes (admin) |
| Templates | HTML rendering only, receive data as variables | None (passive) | Shortcode, potentially Block |

## Data Flow

### 1. Admin Creates a Project (WordPress Backend)

```
WordPress Admin UI
  --> class-meta-boxes.php renders form fields in CPT edit screen
  --> User fills: name, description, logo (WP Media), GitHub URL, installer command
  --> On save: meta-boxes.php sanitizes + saves via update_post_meta()
  --> Data stored: wp_posts (title, content) + wp_postmeta (custom fields)
```

### 2. Claude Creates a Project via API

```
HTTP POST /wp-json/community-master/v1/projects
  --> WordPress REST infrastructure handles auth (Application Password)
  --> class-rest-controller.php::create_item_permissions_check() verifies caps
  --> class-rest-controller.php::create_item() validates + creates post
  --> wp_insert_post() + update_post_meta() for each field
  --> Returns JSON response with created project
```

### 3. Frontend Renders Project Grid

```
User visits page with [community_master] shortcode
  --> WordPress processes shortcode
  --> class-shortcode.php queries: get_posts( type => 'community_project' )
  --> For each post: get_post_meta() for custom fields
  --> Loads templates/tile-grid.php, passes data array
  --> tile-grid.php loops, includes single-tile.php per project
  --> class-assets.php enqueues frontend.css + copy-installer.js
  --> HTML rendered using theme styles + minimal grid CSS
```

### 4. External Reads Projects via API

```
HTTP GET /wp-json/community-master/v1/projects
  --> class-rest-controller.php::get_items() queries CPT
  --> prepare_item_for_response() shapes each project
  --> Returns JSON array with all project data
```

## Custom Post Type Design

### CPT Registration

```php
register_post_type('community_project', [
    'labels'       => [...],
    'public'       => false,      // No single post pages needed
    'show_ui'      => true,       // Show in admin
    'show_in_rest' => true,       // Enable REST API + block editor
    'supports'     => ['title', 'thumbnail', 'custom-fields'],
    'menu_icon'    => 'dashicons-groups',
    'has_archive'  => false,      // No archive page
]);
```

**Key decision: `public => false`** -- Community projects are displayed only via the shortcode on a specific page, not as individual WordPress posts with their own URLs. This avoids SEO confusion and template conflicts.

### Meta Fields (via register_post_meta)

| Meta Key | Type | Required | REST Visible | Purpose |
|----------|------|----------|-------------|---------|
| `_cm_description` | string | Yes | Yes | Short project description |
| `_cm_github_url` | string | Yes | Yes | GitHub repository URL |
| `_cm_installer_command` | string | No | Yes | One-line install command |
| `_cm_logo_url` | string | No | Yes | Project logo URL (from WP Media) |
| `_cm_sort_order` | integer | No | Yes | Display order in grid |

**Why `register_post_meta` over raw `add_meta_box` only:**
- `show_in_rest => true` automatically exposes fields in the REST API
- Block editor compatibility for free
- Sanitization and auth callbacks defined once
- The meta box UI is still needed for a good admin UX, but data registration is separate

**Why underscore prefix (`_cm_`):**
- WordPress convention: leading underscore hides meta from the default "Custom Fields" panel
- `cm_` namespace prefix prevents collisions

### Logo Handling

Use WordPress's built-in `thumbnail` (featured image) support for the logo rather than a separate meta field with a media uploader. This avoids reimplementing the media picker and leverages `get_the_post_thumbnail_url()`.

**Revised approach:** Remove `_cm_logo_url` meta field. Use `'supports' => ['title', 'thumbnail', 'custom-fields']` and the native featured image picker instead.

## REST API Design

### Namespace and Routes

```
community-master/v1/
  GET    /projects          --> get_items()        (list all)
  POST   /projects          --> create_item()      (create new)
  GET    /projects/{id}     --> get_item()         (single)
  PUT    /projects/{id}     --> update_item()      (update)
  DELETE /projects/{id}     --> delete_item()      (delete)
```

### Controller Class

Extend `WP_REST_Controller` following the WordPress handbook pattern:

```php
class CM_REST_Controller extends WP_REST_Controller {
    protected $namespace = 'community-master/v1';
    protected $rest_base = 'projects';

    public function register_routes() { ... }
    public function get_items($request) { ... }
    public function create_item($request) { ... }
    public function get_item($request) { ... }
    public function update_item($request) { ... }
    public function delete_item($request) { ... }
    public function get_items_permissions_check($request) { ... }
    public function create_item_permissions_check($request) { ... }
    public function get_item_schema() { ... }
    public function prepare_item_for_response($post, $request) { ... }
}
```

**Permissions model:**
- `get_items` / `get_item`: Public (no auth required)
- `create_item` / `update_item` / `delete_item`: Requires `edit_posts` capability (Application Password auth for Claude)

### Authentication for Claude

WordPress Application Passwords (built-in since WP 5.6). Claude authenticates with HTTP Basic Auth:
```
Authorization: Basic base64(hulki:xxxx-xxxx-xxxx-xxxx)
```

No plugin-level auth code needed. WordPress handles it.

## Shortcode vs Block Decision

**Phase 1: Shortcode only.** Because:
1. Zero JavaScript build toolchain required (no `@wordpress/scripts`, no webpack, no npm)
2. Works with every WordPress theme without compatibility concerns
3. The display is a simple read-only grid -- no interactive editing in the block editor needed
4. The shortcode block in Gutenberg wraps it fine for the block editor

**Phase 2 (optional): Server-side rendered Gutenberg block.** Worth adding later only if:
- The admin wants visual preview in the editor
- Attributes like "show max N projects" or "filter by tag" need a settings panel

A server-side block (`render_callback` in PHP) avoids React/JSX entirely and reuses the same template files as the shortcode.

## Activation / Deactivation / Uninstall Hooks

### Activation (`register_activation_hook`)
```php
function cm_activate() {
    // 1. Register CPT (so flush works)
    CM_CPT_Project::register();
    // 2. Flush rewrite rules (needed for CPT permalinks)
    flush_rewrite_rules();
    // 3. Set plugin version in options
    update_option('cm_version', CM_VERSION);
}
```

### Deactivation (`register_deactivation_hook`)
```php
function cm_deactivate() {
    // Flush rewrite rules to remove CPT rules
    flush_rewrite_rules();
    // Do NOT delete data -- user may reactivate
}
```

### Uninstall (`uninstall.php`)
```php
// Only runs when plugin is deleted from admin
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Delete all community_project posts and their meta
$posts = get_posts(['post_type' => 'community_project', 'numberposts' => -1]);
foreach ($posts as $post) {
    wp_delete_post($post->ID, true); // force delete, skip trash
}

// Delete plugin options
delete_option('cm_version');
```

## Asset Loading Strategy

### Frontend (public pages)
- Enqueue CSS/JS **only when shortcode is present** on the page
- Use `wp_enqueue_scripts` hook with conditional check
- CSS: minimal grid layout (CSS Grid), no colors/fonts (inherit from theme)
- JS: tiny clipboard script for copy-to-clipboard on installer commands

```php
// In shortcode render method:
public function render($atts) {
    wp_enqueue_style('cm-frontend', plugin_dir_url(__FILE__) . '../assets/css/frontend.css');
    wp_enqueue_script('cm-copy', plugin_dir_url(__FILE__) . '../assets/js/copy-installer.js', [], CM_VERSION, true);
    // ... render template
}
```

### Admin (edit screens)
- Enqueue only on the `community_project` edit screen
- Use `admin_enqueue_scripts` hook with screen check
- Media uploader script for logo (featured image handles this natively)

## Patterns to Follow

### Pattern 1: Singleton Orchestrator
The main plugin class instantiates once and wires everything:

```php
final class Community_Master {
    private static ?self $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }
}
```

**Use for:** The main orchestrator only. Not for individual components.

### Pattern 2: Hook Registration in Constructor
Each component class registers its own hooks in a dedicated method called by the orchestrator:

```php
class CM_CPT_Project {
    public function register_hooks(): void {
        add_action('init', [$this, 'register']);
    }

    public function register(): void {
        register_post_type('community_project', [...]);
        $this->register_meta_fields();
    }
}
```

### Pattern 3: Template Loading with Data Injection
Pass data to templates as extracted variables, never use globals:

```php
$projects = $this->query_projects($atts);
$template = plugin_dir_path(__FILE__) . '../templates/tile-grid.php';
ob_start();
include $template;
$output = ob_get_clean();
return $output;
```

In template: `$projects` is available as a local variable.

## Anti-Patterns to Avoid

### Anti-Pattern 1: Custom Database Tables
**What:** Creating `wp_community_projects` table instead of using CPT.
**Why bad:** Loses WordPress admin UI, REST API, search integration, revision support, media attachment. Forces reimplementing everything WordPress gives for free.
**Instead:** Use Custom Post Type with post meta.

### Anti-Pattern 2: God Class
**What:** Single 1000+ line class handling CPT registration, admin UI, REST API, frontend rendering.
**Why bad:** Impossible to test, modify, or understand in isolation.
**Instead:** One class per concern, wired by orchestrator.

### Anti-Pattern 3: Echoing HTML in PHP Classes
**What:** `echo '<div class="tile">' . $title . '</div>';` inside class methods.
**Why bad:** Mixes logic and presentation, impossible to override in child themes.
**Instead:** Use template files in `/templates/`, loaded via `include`.

### Anti-Pattern 4: Loading Assets Globally
**What:** Enqueuing CSS/JS on every page.
**Why bad:** Performance penalty on pages that don't use the plugin.
**Instead:** Conditional loading -- enqueue only when shortcode renders or on specific admin screens.

### Anti-Pattern 5: Direct `$_POST` Access
**What:** `$value = $_POST['cm_github_url'];`
**Why bad:** No sanitization, no nonce verification, security vulnerability.
**Instead:** Check nonce, use `sanitize_text_field()`, `esc_url()`, etc.

## Suggested Build Order

Based on dependency analysis, components should be built in this order:

```
Phase 1: Foundation
  1. community-master.php (plugin header, constants, autoload)
  2. class-community-master.php (orchestrator skeleton)
  3. class-cpt-project.php (CPT + meta fields)
  4. Activation/deactivation hooks
  --> Testable: CPT appears in WordPress admin, can create posts

Phase 2: Admin Experience
  5. class-meta-boxes.php (custom edit UI for project fields)
  --> Testable: Can fill all project fields in admin UI

Phase 3: Frontend Display
  6. templates/tile-grid.php + single-tile.php
  7. class-shortcode.php (query + render)
  8. class-assets.php (CSS for grid layout)
  9. assets/css/frontend.css (CSS Grid, theme-inheriting)
  10. assets/js/copy-installer.js (clipboard for installer)
  --> Testable: Shortcode renders project grid on frontend

Phase 4: API
  11. class-rest-controller.php (CRUD endpoints)
  --> Testable: Can create/read/update/delete projects via curl

Phase 5: Polish
  12. uninstall.php (cleanup)
  13. First project seeded ("IP-Cam Master")
  --> Testable: Full workflow end-to-end
```

**Build order rationale:**
- CPT must exist before anything else can reference it
- Meta boxes depend on CPT registration
- Shortcode depends on CPT + templates + assets
- REST API depends on CPT but is independent of frontend rendering
- Frontend display should come before API because manual verification of the admin-to-frontend flow validates the data model before exposing it programmatically

## Scalability Considerations

| Concern | At 10 projects | At 50 projects | At 200+ projects |
|---------|----------------|----------------|------------------|
| Query performance | Single `get_posts()` call, trivial | Still fine, single query | Add pagination to shortcode, `posts_per_page` attribute |
| Admin listing | Default CPT list table | Add custom columns (GitHub URL, has installer) | No change needed |
| REST API | Return all | Return all | Add `per_page` and `page` parameters (WP_REST_Controller provides this) |
| Frontend rendering | Simple grid | May need load-more or pagination | CSS Grid handles layout, add pagination |

Given the project scope (meintechblog community projects), 50+ projects is unlikely. The architecture supports it without changes, but optimization is not needed early.

## Sources

- [WordPress Plugin Handbook - Best Practices](https://developer.wordpress.org/plugins/plugin-basics/best-practices/)
- [WordPress REST API - Controller Classes](https://developer.wordpress.org/rest-api/extending-the-rest-api/controller-classes/)
- [WordPress REST API - Adding Custom Endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/)
- [register_post_meta() Reference](https://developer.wordpress.org/reference/functions/register_post_meta/)
- [WordPress Plugin Architecture Best Practices](https://eseospace.com/blog/wordpress-plugin-architecture/)
- [Custom Post Types with Structured Meta Fields (without ACF)](https://fullstackdigital.io/blog/build-custom-post-types-with-structured-meta-fields-in-wordpress-without-3rd-party-plugins-like-acf/)
- [Making Post Meta Accessible in Block Editor](https://olliewp.com/lesson/making-post-meta-accessible-the-block-editor/)
- [WordPress Custom Meta Boxes Handbook](https://developer.wordpress.org/plugins/metadata/custom-meta-boxes/)
