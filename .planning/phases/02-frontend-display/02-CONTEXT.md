# Phase 2: Frontend Display - Context

**Gathered:** 2026-03-24
**Status:** Ready for planning

<domain>
## Phase Boundary

Deliver a `[community-master]` shortcode that renders all community projects as a responsive tile grid on any WordPress page. Each tile shows logo, name, description, GitHub link, and optional one-line installer with copy-to-clipboard. The display integrates seamlessly into the active WordPress theme.

</domain>

<decisions>
## Implementation Decisions

### Claude's Discretion
All frontend design decisions are delegated to Claude. The following guidelines apply:

- **Kachel-Design:** Clean card layout — logo at top, name as heading, description text, GitHub link as button/link. Keep it simple, no heavy styling. Use theme fonts and colors.
- **Grid-Layout:** CSS Grid or Flexbox, 3 columns desktop / 2 tablet / 1 mobile (per REQUIREMENTS). Standard gaps, no fancy animations.
- **Installer-Box:** Monospace code box with a small copy button (clipboard icon or "Copy" text). Brief visual feedback on copy (e.g., "Copied!" text or checkmark). Only shown when `_community_master_installer` meta is non-empty.
- **Empty State:** Simple centered text message when no projects exist (e.g., "Noch keine Community-Projekte vorhanden.")
- **CSS approach:** Plugin-scoped CSS file loaded only on pages with the shortcode. Minimal, functional styling. Theme-compatible (uses CSS variables or inherits theme defaults where possible).
- **JavaScript:** Minimal — only for copy-to-clipboard (`navigator.clipboard.writeText()`). Inline or tiny enqueued script.
- **Hover effects:** Subtle, theme-appropriate. Nothing flashy.
- **GitHub link:** External link (opens in new tab), clear visual indicator (GitHub icon or "View on GitHub" text).
- **Output escaping:** All dynamic content escaped with `esc_html()`, `esc_url()`, `esc_attr()` — XSS prevention is non-negotiable.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase 1 Implementation
- `community-master.php` — Plugin entry point, understand how assets should be enqueued
- `includes/class-community-master.php` — Singleton orchestrator, new shortcode class wires in here
- `includes/class-cpt-project.php` — Meta field keys: `_community_master_description`, `_community_master_github_url`, `_community_master_installer`. CPT slug: `community_project`. Post type is `public => false` so no archive page — shortcode is the only frontend.

### Research
- `.planning/research/ARCHITECTURE.md` — Shortcode rendering pattern, asset loading
- `.planning/research/FEATURES.md` — Table stakes (responsive grid, copy-to-clipboard), anti-features (no multiple layouts)
- `.planning/research/PITFALLS.md` — Output escaping, conditional asset loading

### Requirements
- `.planning/REQUIREMENTS.md` — FRONT-01..07, SEC-02, SEC-03

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `CM_CPT_Project::register_meta_fields()` — Meta field keys defined here, use same keys for queries
- `get_the_post_thumbnail_url()` — WordPress native for logo retrieval
- `menu_order` field — Already used for sorting in Phase 1, query with `orderby => 'menu_order'`

### Established Patterns
- Class-per-concern: New shortcode class should follow `CM_Meta_Boxes` / `CM_Admin_Columns` pattern
- Singleton orchestrator: `CM_Community_Master` instantiates all classes
- PHP 8.2+ features safe to use (typed properties, readonly, etc.)

### Integration Points
- `includes/class-community-master.php` — Add `new CM_Shortcode()` in constructor
- `wp_enqueue_style()` / `wp_enqueue_script()` — Conditional loading on shortcode pages
- `add_shortcode('community-master', ...)` — Register in init or constructor

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches. User delegated all frontend decisions to Claude.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 02-frontend-display*
*Context gathered: 2026-03-24*
