# Phase 2: Frontend Display - Research

**Researched:** 2026-03-24
**Domain:** WordPress shortcode rendering, responsive CSS Grid, clipboard API
**Confidence:** HIGH

## Summary

Phase 2 delivers the public-facing output of the Community Master plugin: a `[community-master]` shortcode that queries all `community_project` CPT posts and renders them as a responsive tile grid. The technical domain is well-understood WordPress shortcode development with vanilla CSS Grid and a minimal clipboard JavaScript snippet.

The existing codebase from Phase 1 provides a solid foundation. The CPT is registered with `public => false` (no archive pages), meta fields are registered via `register_post_meta()` with sanitization, and the class-per-concern pattern is established. Phase 2 adds three new files (shortcode class, CSS, JS) plus two template files, wired into the existing orchestrator.

**Primary recommendation:** Use the register-then-enqueue pattern for conditional asset loading. Enqueue CSS inside the shortcode render callback (WordPress allows late CSS enqueuing). Keep JS minimal -- a single inline script or tiny enqueued file for clipboard copy.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
None -- all frontend decisions delegated to Claude's discretion.

### Claude's Discretion
- Kachel-Design: Clean card layout -- logo at top, name as heading, description text, GitHub link as button/link. Keep it simple, no heavy styling. Use theme fonts and colors.
- Grid-Layout: CSS Grid or Flexbox, 3 columns desktop / 2 tablet / 1 mobile. Standard gaps, no fancy animations.
- Installer-Box: Monospace code box with a small copy button (clipboard icon or "Copy" text). Brief visual feedback on copy (e.g., "Copied!" text or checkmark). Only shown when `_community_master_installer` meta is non-empty.
- Empty State: Simple centered text message when no projects exist (e.g., "Noch keine Community-Projekte vorhanden.")
- CSS approach: Plugin-scoped CSS file loaded only on pages with the shortcode. Minimal, functional styling. Theme-compatible.
- JavaScript: Minimal -- only for copy-to-clipboard (`navigator.clipboard.writeText()`). Inline or tiny enqueued script.
- Hover effects: Subtle, theme-appropriate. Nothing flashy.
- GitHub link: External link (opens in new tab), clear visual indicator.
- Output escaping: All dynamic content escaped with `esc_html()`, `esc_url()`, `esc_attr()`.

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| FRONT-01 | Shortcode `[community-master]` zeigt Projekt-Grid an | Shortcode registration pattern, WP_Query for CPT, template loading via ob_start/include |
| FRONT-02 | Kacheln zeigen Logo, Name, Beschreibung und GitHub-Link | Template partial using `get_the_post_thumbnail()`, `get_the_title()`, `get_post_meta()` for each field |
| FRONT-03 | Grid ist responsive (3 Spalten Desktop, 2 Tablet, 1 Mobile) | CSS Grid with `auto-fill`/`minmax` or explicit media queries at 768px and 480px breakpoints |
| FRONT-04 | One-Line-Installer wird nur angezeigt wenn vorhanden, in kopierbarer Code-Box | Conditional check on `_community_master_installer` meta, `<pre><code>` block with `esc_html()` |
| FRONT-05 | Copy-to-Clipboard Button mit visuellem Feedback | `navigator.clipboard.writeText()` with `execCommand('copy')` fallback, button text swap for feedback |
| FRONT-06 | Design integriert sich ins bestehende WordPress-Theme | Minimal CSS that inherits `font-family`, `color`, `background` from theme. No hardcoded colors. |
| FRONT-07 | Empty State zeigt hilfreiche Nachricht wenn keine Projekte existieren | Post count check before rendering grid, centered message fallback |
| SEC-02 | Alle Frontend-Ausgaben werden escaped | `esc_html()` for text, `esc_url()` for URLs, `esc_attr()` for attributes -- every output point |
| SEC-03 | One-Line-Installer Output wird sicher escaped | `esc_html()` inside `<code>` element, `esc_attr()` for data attribute passed to JS |
</phase_requirements>

## Standard Stack

### Core

No external libraries required. Pure WordPress APIs + vanilla CSS/JS.

| API/Tool | Version | Purpose | Why Standard |
|----------|---------|---------|--------------|
| `add_shortcode()` | WP Core | Register `[community-master]` shortcode | WordPress standard for embedding dynamic content |
| `get_posts()` | WP Core | Query community_project CPT posts | Lightweight query for known post type, simpler than WP_Query for this use case |
| `get_post_meta()` | WP Core | Retrieve meta field values | Standard WordPress meta API |
| `get_the_post_thumbnail()` | WP Core | Render project logo with responsive srcset | Built-in responsive image handling |
| CSS Grid | CSS3 | Responsive tile layout | Native browser support, no framework needed |
| `navigator.clipboard.writeText()` | Web API | Copy installer command to clipboard | Baseline browser support since March 2025 |

### Supporting

| Tool | Purpose | When to Use |
|------|---------|-------------|
| `wp_register_style()` / `wp_enqueue_style()` | Conditional CSS loading | Register on `wp_enqueue_scripts`, enqueue inside shortcode callback |
| `wp_register_script()` / `wp_enqueue_script()` | Conditional JS loading | Same pattern -- register early, enqueue when shortcode renders |
| `wp_add_inline_script()` | Attach small JS to registered script | Alternative to separate JS file for clipboard logic |
| `ob_start()` / `ob_get_clean()` | Capture template output | Standard WordPress pattern for shortcode render callbacks |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| CSS Grid | Flexbox | Flexbox works but CSS Grid is cleaner for equal-height cards with `grid-template-columns` |
| Separate JS file | Inline `<script>` in template | Enqueued file is cleaner, allows caching, follows WP standards |
| `get_posts()` | `WP_Query` | WP_Query is more powerful but unnecessary for a simple CPT query with no pagination |

## Architecture Patterns

### New Files to Create

```
community-master/
  includes/
    class-shortcode.php         # Shortcode registration + render logic
  templates/
    tile-grid.php               # Grid wrapper template
    single-tile.php             # Individual card template partial
  assets/
    css/
      frontend.css              # Responsive grid + card styles
    js/
      copy-installer.js         # Clipboard copy with feedback
```

### Pattern 1: Shortcode Class with Conditional Asset Loading

**What:** Register the shortcode and handle asset enqueuing in a single class. Register assets early, enqueue only when the shortcode actually renders.

**When to use:** Always -- this is the standard WordPress pattern.

**Example:**
```php
class CM_Shortcode {

    public function __construct() {
        add_shortcode('community-master', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_assets(): void {
        wp_register_style(
            'community-master-frontend',
            plugins_url('assets/css/frontend.css', COMMUNITY_MASTER_FILE),
            [],
            COMMUNITY_MASTER_VERSION
        );
        wp_register_script(
            'community-master-copy',
            plugins_url('assets/js/copy-installer.js', COMMUNITY_MASTER_FILE),
            [],
            COMMUNITY_MASTER_VERSION,
            ['strategy' => 'defer', 'in_footer' => true]
        );
    }

    public function render(array $atts): string {
        // Enqueue only when shortcode is actually used
        wp_enqueue_style('community-master-frontend');
        wp_enqueue_script('community-master-copy');

        $projects = get_posts([
            'post_type'      => 'community_project',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);

        if (empty($projects)) {
            return '<div class="cm-empty-state">'
                . esc_html__('Noch keine Community-Projekte vorhanden.', 'community-master')
                . '</div>';
        }

        ob_start();
        include COMMUNITY_MASTER_DIR . 'templates/tile-grid.php';
        return ob_get_clean();
    }
}
```

**Source:** WordPress Plugin Handbook shortcode pattern + conditional enqueue best practice.

### Pattern 2: Template with Data Injection

**What:** Pass `$projects` array to template files via local scope. Templates receive data, never query directly.

**When to use:** Always -- separates logic from presentation.

**Example (tile-grid.php):**
```php
<?php defined('ABSPATH') || exit; ?>
<div class="cm-grid">
    <?php foreach ($projects as $project): ?>
        <?php include COMMUNITY_MASTER_DIR . 'templates/single-tile.php'; ?>
    <?php endforeach; ?>
</div>
```

**Example (single-tile.php):**
```php
<?php
defined('ABSPATH') || exit;
$description = get_post_meta($project->ID, '_community_master_description', true);
$github_url  = get_post_meta($project->ID, '_community_master_github_url', true);
$installer   = get_post_meta($project->ID, '_community_master_installer', true);
?>
<div class="cm-tile">
    <?php if (has_post_thumbnail($project->ID)): ?>
        <div class="cm-tile__logo">
            <?php echo get_the_post_thumbnail($project->ID, 'medium'); ?>
        </div>
    <?php endif; ?>

    <h3 class="cm-tile__title"><?php echo esc_html(get_the_title($project->ID)); ?></h3>

    <?php if ($description): ?>
        <p class="cm-tile__description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>

    <?php if ($installer): ?>
        <div class="cm-tile__installer">
            <pre><code><?php echo esc_html($installer); ?></code></pre>
            <button class="cm-copy-btn" type="button"
                    data-copy="<?php echo esc_attr($installer); ?>">
                <?php esc_html_e('Copy', 'community-master'); ?>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($github_url): ?>
        <a class="cm-tile__github" href="<?php echo esc_url($github_url); ?>"
           target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('View on GitHub', 'community-master'); ?>
        </a>
    <?php endif; ?>
</div>
```

### Pattern 3: Clipboard Copy with Fallback

**What:** Use Clipboard API with `execCommand` fallback and visual feedback.

**Example (copy-installer.js):**
```javascript
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.cm-copy-btn');
    if (!btn) return;

    var text = btn.getAttribute('data-copy');
    var originalLabel = btn.textContent;

    function showFeedback() {
        btn.textContent = 'Copied!';
        btn.classList.add('cm-copy-btn--copied');
        setTimeout(function() {
            btn.textContent = originalLabel;
            btn.classList.remove('cm-copy-btn--copied');
        }, 2000);
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(showFeedback);
    } else {
        // Fallback for non-HTTPS or older browsers
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showFeedback();
    }
});
```

### Anti-Patterns to Avoid

- **Enqueuing CSS/JS globally:** Never load `frontend.css` on all pages. Use register-then-enqueue pattern inside shortcode callback.
- **Echoing HTML in class methods:** All HTML goes in `/templates/` files, not inlined in `CM_Shortcode::render()`.
- **Unescaped output:** Every `echo` in templates must use `esc_html()`, `esc_url()`, or `esc_attr()`. The installer field is especially dangerous -- it contains shell commands.
- **Using `echo` instead of `return` in shortcode:** Shortcode callbacks must return the output string, not echo it. Use `ob_start()`/`ob_get_clean()`.
- **Hardcoding colors/fonts in CSS:** Use `inherit`, `currentColor`, or CSS custom properties to match the active theme.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Responsive images | Custom `srcset` generation | `get_the_post_thumbnail()` | WordPress auto-generates responsive `srcset` for all registered image sizes |
| Clipboard copy | Custom input selection + range API | `navigator.clipboard.writeText()` + `execCommand` fallback | Standard Web API, baseline since March 2025 |
| CSS scoping | BEM naming manually | `.cm-` prefix on all classes | WordPress has no CSS modules; manual prefix is the standard approach |
| Image sizing | Manual CSS `width`/`height` | `add_image_size('cm-logo', 200, 200, true)` | WordPress handles cropping, srcset, retina |

## Common Pitfalls

### Pitfall 1: CSS Not Loading in `<head>` When Enqueued Inside Shortcode

**What goes wrong:** When `wp_enqueue_style()` is called inside the shortcode render callback, WordPress has already passed the `wp_head` action. The CSS gets added to the footer instead, causing a flash of unstyled content (FOUC).

**Why it happens:** WordPress normally outputs `<link>` tags in `wp_head`. By the time a shortcode renders (during `the_content` filter), `wp_head` has already fired.

**How to avoid:** This is actually acceptable in modern WordPress. Since WP 6.3, `wp_enqueue_style()` called after `wp_head` outputs the style tag at the current position in the document. For a small CSS file (~2KB), this causes no visible FOUC. Alternatively, use `has_shortcode()` in `wp_enqueue_scripts` to detect the shortcode in page content before rendering, but this approach fails for shortcodes in widgets or template parts.

**Recommendation:** Enqueue inside the shortcode callback. The CSS file is small enough that inline placement is fine. This is the WordPress-recommended approach for shortcode-specific assets.

### Pitfall 2: Shortcode Returning vs Echoing

**What goes wrong:** Using `echo` in the shortcode callback outputs HTML at the top of the page content, before other content.

**Why it happens:** WordPress collects shortcode output by capturing the return value. If you `echo` instead of `return`, the output appears before WordPress processes the rest of the content.

**How to avoid:** Always use `ob_start()` to capture template includes, then `return ob_get_clean()`.

### Pitfall 3: XSS via Installer Field

**What goes wrong:** The one-line installer contains shell commands (e.g., `curl -sSL https://... | bash`). If rendered without escaping, an attacker who can edit a project could inject `<script>` tags.

**Why it happens:** The installer field is user input stored in postmeta. Even though it is sanitized on save with `sanitize_text_field()`, output escaping is still required.

**How to avoid:** Always use `esc_html()` when displaying installer text inside `<code>` blocks. Use `esc_attr()` when passing the value via `data-copy` attribute to JavaScript.

**Warning signs:** Any `echo $installer` or `echo $description` without `esc_html()` wrapper.

### Pitfall 4: Missing Empty State

**What goes wrong:** Page with `[community-master]` shortcode shows nothing when no projects exist. Visitors see blank space and think the page is broken.

**How to avoid:** Check `empty($projects)` before rendering the grid. Return a styled empty-state message.

### Pitfall 5: Logo Images Without Fallback

**What goes wrong:** Cards without a featured image show broken layout -- the logo area collapses and the card looks inconsistent with other cards.

**How to avoid:** Use `has_post_thumbnail()` check. When no thumbnail exists, either skip the logo area entirely or show a placeholder (a dashicon or SVG fallback).

## Code Examples

### CSS Grid Layout (frontend.css)

```css
/* Source: CSS Grid specification, standard responsive pattern */

/* Grid container */
.cm-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    padding: 1rem 0;
}

/* Tablet: 2 columns */
@media (max-width: 768px) {
    .cm-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Mobile: 1 column */
@media (max-width: 480px) {
    .cm-grid {
        grid-template-columns: 1fr;
    }
}

/* Card tile */
.cm-tile {
    display: flex;
    flex-direction: column;
    border: 1px solid currentColor;
    border-radius: 0.5rem;
    padding: 1.25rem;
    opacity: 0.85;
    border-color: rgba(128, 128, 128, 0.3);
    transition: box-shadow 0.2s ease;
}

.cm-tile:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Logo */
.cm-tile__logo {
    text-align: center;
    margin-bottom: 1rem;
}

.cm-tile__logo img {
    max-width: 100%;
    height: auto;
    border-radius: 0.25rem;
}

/* Title inherits theme heading styles */
.cm-tile__title {
    margin: 0 0 0.5rem;
}

/* Description */
.cm-tile__description {
    flex: 1;
    margin: 0 0 1rem;
}

/* Installer code box */
.cm-tile__installer {
    position: relative;
    margin-bottom: 1rem;
}

.cm-tile__installer pre {
    margin: 0;
    padding: 0.75rem;
    overflow-x: auto;
    background: rgba(128, 128, 128, 0.1);
    border-radius: 0.25rem;
    font-size: 0.85em;
}

.cm-tile__installer code {
    white-space: pre;
    word-break: break-all;
}

/* Copy button */
.cm-copy-btn {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75em;
    cursor: pointer;
    border: 1px solid rgba(128, 128, 128, 0.3);
    border-radius: 0.25rem;
    background: inherit;
    color: inherit;
}

.cm-copy-btn--copied {
    border-color: green;
    color: green;
}

/* GitHub link */
.cm-tile__github {
    display: inline-block;
    margin-top: auto;
    font-weight: 600;
}

/* Empty state */
.cm-empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: inherit;
    opacity: 0.6;
}
```

### Wiring Into Orchestrator (class-community-master.php)

```php
// In constructor, add:
new CM_Shortcode();
```

### Entry Point Update (community-master.php)

```php
// Add after existing require_once lines:
require_once COMMUNITY_MASTER_DIR . 'includes/class-shortcode.php';
```

### Custom Image Size Registration

```php
// In CM_CPT_Project::register() or a dedicated hook:
add_image_size('cm-logo', 200, 200, true);
```

## Integration Points

### Files to Modify

| File | Change | Reason |
|------|--------|--------|
| `community-master.php` | Add `require_once` for `class-shortcode.php` | Load the new class file |
| `includes/class-community-master.php` | Add `new CM_Shortcode()` in constructor | Wire shortcode into plugin lifecycle |

### Files to Create

| File | Purpose |
|------|---------|
| `includes/class-shortcode.php` | Shortcode registration, asset registration, render logic |
| `templates/tile-grid.php` | Grid wrapper HTML template |
| `templates/single-tile.php` | Individual card HTML template |
| `assets/css/frontend.css` | Responsive grid + card styles |
| `assets/js/copy-installer.js` | Clipboard copy with visual feedback |

### Data Dependencies

| Data | Source | Access Pattern |
|------|--------|----------------|
| Project title | `wp_posts.post_title` | `get_the_title($project->ID)` |
| Project logo | Featured image (thumbnail) | `get_the_post_thumbnail($project->ID, 'medium')` |
| Description | `_community_master_description` meta | `get_post_meta($project->ID, '_community_master_description', true)` |
| GitHub URL | `_community_master_github_url` meta | `get_post_meta($project->ID, '_community_master_github_url', true)` |
| Installer | `_community_master_installer` meta | `get_post_meta($project->ID, '_community_master_installer', true)` |
| Sort order | `wp_posts.menu_order` | Query with `'orderby' => 'menu_order'` |

## Project Constraints (from CLAUDE.md)

- Pure PHP plugin, no external dependencies, WordPress core APIs only
- PHP 8.2+ features safe to use (typed properties, readonly, etc.)
- Class-per-concern pattern (follow `CM_Meta_Boxes` / `CM_Admin_Columns` established pattern)
- All strings wrapped in `__()` / `_e()` with text domain `community-master`
- WordPress path functions only (`plugin_dir_path()`, `plugins_url()`) -- no hardcoded paths
- Plugin constants: `COMMUNITY_MASTER_VERSION`, `COMMUNITY_MASTER_FILE`, `COMMUNITY_MASTER_DIR`

## Sources

### Primary (HIGH confidence)
- WordPress Plugin Handbook - Shortcode API (established WordPress pattern)
- WordPress `wp_enqueue_style()` / `wp_enqueue_script()` reference docs
- Existing codebase: `class-meta-boxes.php`, `class-admin-columns.php` (established patterns)
- CSS Grid specification (MDN, W3C)

### Secondary (MEDIUM confidence)
- [Clipboard API: writeText - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Clipboard/writeText) - Baseline since March 2025
- [Can I Use - Clipboard writeText](https://caniuse.com/mdn-api_clipboard_writetext) - 97%+ global support
- [Conditional Scripts/Styles for WordPress Shortcodes](https://austingil.com/conditional-scripts-styles-for-wordpress-shortcodes/) - enqueue pattern
- [WordPress Plugin Handbook - Security: Output Escaping](https://developer.wordpress.org/plugins/security/securing-output/)

### Tertiary (LOW confidence)
None -- all claims verified with official sources.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - WordPress core APIs, no external dependencies
- Architecture: HIGH - follows established patterns from Phase 1 codebase
- Pitfalls: HIGH - well-documented WordPress security patterns, official docs
- CSS/JS: HIGH - CSS Grid and Clipboard API are mature, widely supported standards

**Research date:** 2026-03-24
**Valid until:** 2026-04-24 (stable WordPress APIs, no expected changes)
