# Phase 3: REST API - Context

**Gathered:** 2026-03-24
**Status:** Ready for planning

<domain>
## Phase Boundary

Ensure external clients (especially Claude) can create, update, and delete community projects programmatically via the WordPress REST API. The CPT already has `show_in_rest => true` and all meta fields are registered with `show_in_rest => true` — so the standard WordPress REST API already provides CRUD. This phase verifies, hardens, and documents the API surface.

</domain>

<decisions>
## Implementation Decisions

### Claude's Discretion
All REST API decisions are delegated to Claude. The following guidelines apply:

- **API approach:** Leverage the built-in WordPress REST API (`/wp-json/wp/v2/community_project/`) rather than building custom endpoints. The CPT and meta fields already have `show_in_rest => true`. Only add custom endpoints if the standard API is insufficient.
- **Authentication:** Use WordPress Application Passwords (already configured for user "hulki" on meintechblog.de). No JWT or OAuth needed.
- **Permission checks:** The CPT already uses custom `capability_type => 'community_project'` with `map_meta_cap => true`. The REST API inherits these permissions automatically. Verify that unauthenticated requests are properly rejected.
- **Featured Image upload:** Use the standard WordPress media upload endpoint (`/wp-json/wp/v2/media`) to upload logos, then set `featured_media` on the project. This is a two-step process (upload image → set on post).
- **Meta field access:** All three meta fields (`_community_master_description`, `_community_master_github_url`, `_community_master_installer`) are already REST-exposed via `register_post_meta`. Verify they work correctly for read and write.
- **GitHub URL validation:** The `sanitize_callback` (`esc_url_raw`) runs on REST saves too. Consider adding a `rest_pre_insert_community_project` filter for domain-specific validation (github.com prefix check) if the meta box validation doesn't cover REST saves.
- **Error responses:** Use standard WordPress REST API error format. No custom error handling needed.
- **Testing approach:** Create a simple test script or curl examples that verify CRUD operations work with Application Password auth.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase 1 Implementation
- `includes/class-cpt-project.php` — CPT registration with `show_in_rest => true`, meta field registration with `sanitize_callback` and `auth_callback`
- `includes/class-meta-boxes.php` — GitHub URL validation logic (strpos check for `https://github.com/`)
- `community-master.php` — Plugin entry point

### Research
- `.planning/research/ARCHITECTURE.md` — REST API controller patterns
- `.planning/research/PITFALLS.md` — REST API security pitfalls, permission callbacks

### Requirements
- `.planning/REQUIREMENTS.md` — API-01..05, SEC-05

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `CM_CPT_Project::register()` — CPT with `show_in_rest => true` already provides REST endpoints
- `CM_CPT_Project::register_meta_fields()` — All 3 meta fields have `show_in_rest => true`, `sanitize_callback`, and `auth_callback`
- Application Password already configured on meintechblog.de (user: hulki)

### Established Patterns
- `sanitize_callback` on `register_post_meta` handles REST API input sanitization
- `auth_callback` on meta fields checks `edit_community_projects` capability
- Custom `capability_type` ensures only authorized users can CRUD via REST

### Integration Points
- WordPress REST API automatically creates: `GET/POST /wp-json/wp/v2/community_project`, `GET/PUT/PATCH/DELETE /wp-json/wp/v2/community_project/{id}`
- Meta fields accessible via `meta` object in REST responses/requests
- Featured image via `featured_media` field (standard WP REST API)

</code_context>

<specifics>
## Specific Ideas

- The main work may be verification and hardening rather than new code, since WP REST API handles most of it
- A GitHub URL validation filter for REST saves would close the gap between meta box validation and REST API validation
- A helper script or documentation showing how Claude should call the API would be valuable for Phase 4

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 03-rest-api*
*Context gathered: 2026-03-24*
