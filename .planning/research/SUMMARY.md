# Research Summary: Community Master WordPress Plugin

**Domain:** WordPress plugin for community project showcase
**Researched:** 2026-03-24
**Overall confidence:** HIGH

## Executive Summary

Community Master is a straightforward WordPress plugin that uses well-established, stable WordPress core APIs. The entire stack is zero-dependency at runtime -- every feature (Custom Post Types, meta fields, REST API, shortcodes) is built on APIs that have been stable for 5+ years in WordPress core. This makes the project low-risk from a technology standpoint.

The recommended approach is a pure PHP plugin with no JavaScript build toolchain. The plugin registers a `community_project` Custom Post Type with native meta fields (`register_post_meta`), renders a tile grid via a `[community_master]` shortcode, and exposes custom REST API endpoints for programmatic access by Claude. Authentication uses WordPress's built-in Application Passwords, which are already configured on meintechblog.de.

The key architectural decision is to avoid external dependencies entirely. ACF, Meta Box, and CMB2 are all unnecessary for 5-6 simple text/URL fields. A Gutenberg block is unnecessary when a shortcode provides maximum theme compatibility. Custom database tables are unnecessary for ~50 records. This "use WordPress core only" approach minimizes maintenance burden, eliminates plugin compatibility issues, and keeps the codebase small enough for a single developer to understand completely.

PHP 8.2+ is the target runtime, with 8.3 recommended. WordPress 7.0 (scheduled April 2026) drops PHP 7.2/7.3 support entirely, confirming the direction toward modern PHP. The plugin should use strict typing, readonly properties, and named arguments where they improve clarity.

## Key Findings

**Stack:** Pure PHP plugin using WordPress core APIs only (CPT, register_post_meta, register_rest_route, add_shortcode). No external runtime dependencies. WPCS 3.x for code quality via Composer dev dependency.

**Architecture:** Class-per-concern structure (CPT, Meta, REST, Shortcode, Renderer) wired by a single orchestrator class. Templates separated from logic. Conditional asset loading. Featured Image for logos instead of custom media fields.

**Critical pitfall:** REST API permission callbacks must enforce capability checks from day one. The plugin's API surface is designed for programmatic access (Claude via Application Passwords), making it the primary attack vector if permissions are misconfigured.

## Implications for Roadmap

Based on research, suggested phase structure:

1. **Foundation** - Plugin scaffold, CPT registration, meta field registration, activation hooks
   - Addresses: Core data model (CPT + meta), admin UI (free with CPT)
   - Avoids: Rewrite flush pitfall (activation hook), capability mapping pitfall (map_meta_cap)
   - Deliverable: CPT visible in WordPress admin, can create/edit projects

2. **Admin Experience** - Meta boxes for custom fields in edit screen, featured image integration
   - Addresses: Data entry workflow for project fields (GitHub URL, installer command, description)
   - Avoids: Sanitization gaps (sanitize on save), nonce verification for forms
   - Deliverable: Full admin editing experience with all project fields

3. **Frontend Display** - Shortcode, tile grid template, responsive CSS, copy-to-clipboard JS
   - Addresses: Public-facing output on meintechblog.de/community-master
   - Avoids: XSS via output escaping, global CSS loading, CSS class collisions with theme
   - Deliverable: `[community_master]` shortcode renders responsive project grid

4. **REST API** - Custom endpoints for CRUD, permission callbacks, input validation
   - Addresses: Programmatic project management (Claude creating projects via API)
   - Avoids: Open endpoints, missing auth, endpoint registration timing issues
   - Deliverable: Claude can create/update/delete projects via HTTP

5. **Polish and Launch** - First project seeded (IP-Cam Master), uninstall cleanup, deployment
   - Addresses: End-to-end validation, clean uninstall, production deployment
   - Avoids: Orphaned data on uninstall, hardcoded paths
   - Deliverable: Live on meintechblog.de with IP-Cam Master as first project

**Phase ordering rationale:**
- CPT must exist before meta boxes, shortcode, or REST endpoints can reference it
- Admin experience before frontend ensures data model is validated with real content before building display layer
- Frontend before REST API because visual verification of the admin-to-display flow validates the data model before exposing it programmatically
- REST API after frontend because it depends on the same CPT/meta but is an independent consumer
- Polish last because it depends on all other phases being functional

**Research flags for phases:**
- Phase 1-5: Standard WordPress patterns, unlikely to need additional research
- Phase 3 (Frontend): May need theme-specific CSS investigation to ensure grid integrates cleanly with meintechblog.de's current theme
- Phase 4 (REST API): Should verify Application Password configuration works from external clients before building endpoints

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All recommended technologies are WordPress core APIs, stable for 5+ years. PHP version recommendations verified against official WordPress compatibility matrix. |
| Features | HIGH | Feature set is well-scoped by PROJECT.md. Table stakes are standard WordPress plugin patterns. Anti-features are clearly justified. |
| Architecture | HIGH | Class-per-concern with shortcode rendering is the canonical WordPress plugin pattern. No novel architecture decisions. |
| Pitfalls | HIGH | Pitfalls are well-documented in WordPress security reports and developer handbook. XSS and CSRF are the top 2 WordPress vulnerability types (53% and 17% of CVEs respectively). |

## Gaps to Address

- **Theme CSS specifics**: The exact CSS needed to integrate with meintechblog.de's current theme cannot be determined until Phase 3. May need to inspect the live theme's grid/card patterns.
- **Application Password verification**: Should test that the existing Application Password setup on meintechblog.de works with external HTTP clients (curl) before building REST endpoints in Phase 4.
- **WordPress version on meintechblog.de**: Research assumes 6.6+. Should verify the exact WordPress version running on the production site.
- **PHP version on meintechblog.de**: Research targets PHP 8.2+. Should verify the server's PHP version before using modern syntax features.
