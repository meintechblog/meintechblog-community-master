# Phase 1: Plugin Core & Admin - Context

**Gathered:** 2026-03-24
**Status:** Ready for planning

<domain>
## Phase Boundary

Deliver a fully functional WordPress plugin that registers a Custom Post Type "community_project" with custom meta fields, admin meta boxes, and proper security. After this phase, admins and editors can create, edit, reorder, and delete community projects entirely within the WordPress backend.

</domain>

<decisions>
## Implementation Decisions

### Description Field
- **D-01:** Project description is a simple textarea meta field, NOT the WordPress post content/editor. Keeps the plugin lean — no Gutenberg editor complexity needed for short project descriptions.

### Capabilities
- **D-02:** Use custom capability_type `community_project` with `map_meta_cap => true`. Both Admins and Editors can manage projects. Custom capabilities must be added on plugin activation and removed on uninstall.

### Admin Columns
- **D-03:** The CPT list table in wp-admin shows custom columns: Logo Thumbnail, GitHub URL, and Sortierung (menu_order value). These give quick overview without opening each project.

### Plugin Identity
- **D-04:** Plugin name: "Community Master". Text domain: `community-master`. Menu label: "Community Master". Plugin slug/folder: `meintechblog-community-master`.

### Claude's Discretion
- Meta box layout and grouping (single meta box vs. multiple)
- Admin CSS for meta boxes (minimal, functional styling)
- Exact sanitize/escape functions per field type
- Activation/deactivation hook implementation details

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Research
- `.planning/research/ARCHITECTURE.md` — Plugin file structure, CPT registration pattern, meta field approach
- `.planning/research/PITFALLS.md` — Security pitfalls (map_meta_cap, nonce verification, sanitization patterns)
- `.planning/research/STACK.md` — PHP version target, WordPress API recommendations

### Project
- `.planning/PROJECT.md` — Core value, constraints, key decisions
- `.planning/REQUIREMENTS.md` — FOUND-01..04, FIELD-01..06, SEC-01, SEC-04

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- None — greenfield plugin, no existing code

### Established Patterns
- None — first phase, patterns will be established here

### Integration Points
- WordPress Plugin API: `register_post_type()`, `register_post_meta()`, `add_meta_box()`
- WordPress Admin: Hook into admin menu, list table columns
- WordPress Capabilities: `add_cap()` on activation, `remove_cap()` on uninstall

</code_context>

<specifics>
## Specific Ideas

- Plugin folder name matches GitHub repo: `meintechblog-community-master`
- Post type slug: `community_project` (with underscore, WordPress convention)
- Meta fields: `_community_master_github_url`, `_community_master_description`, `_community_master_installer` (prefixed with underscore for hidden from custom fields UI)
- GitHub URL validation: must start with `https://github.com/`

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 01-plugin-core-admin*
*Context gathered: 2026-03-24*
