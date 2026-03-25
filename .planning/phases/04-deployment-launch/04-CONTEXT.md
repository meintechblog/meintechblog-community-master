# Phase 4: Deployment & Launch - Context

**Gathered:** 2026-03-24
**Status:** Ready for planning

<domain>
## Phase Boundary

Deploy the Community Master plugin to meintechblog.de, create the /community-master page with the shortcode, seed the first project (IP-Cam Master), push the plugin code to GitHub, and create a distributable ZIP release.

</domain>

<decisions>
## Implementation Decisions

### Plugin Distribution
- **D-01:** Create a distributable ZIP file of the plugin (excluding `.planning/`, `tests/`, `.git/`). The ZIP should be available as a GitHub Release so users can download and install it via WordPress Plugin Upload.
- **D-02:** Claude installs the plugin on meintechblog.de using the most pragmatic method available (REST API upload or wp-cli if available).

### IP-Cam Master Project Data
- **D-03:** First project to seed:
  - **Name:** IP-Cam Master
  - **Description:** One-click camera onboarding for UniFi Protect. Discover cameras in the network, and the app handles everything — container creation, stream transcoding, ONVIF wrapping, and Protect adoption.
  - **GitHub URL:** https://github.com/meintechblog/ip-cam-master
  - **One-Line Installer:** `curl -fsSL https://raw.githubusercontent.com/meintechblog/ip-cam-master/main/install.sh | bash`
  - **Logo:** Platzhalter-Icon (generisches Kamera-Icon), wird später manuell ersetzt

### GitHub Repository
- **D-04:** Create `meintechblog/meintechblog-community-master` on GitHub as public repo. Push all code via SSH. Create a v1.0.0 release with the distributable ZIP.

### WordPress Page
- **D-05:** Create a WordPress page at /community-master with the `[community-master]` shortcode. The page should have a simple title like "Community Master" or "Community-Projekte".

### Authentication
- **D-06:** Use existing Application Password for WordPress REST API (user: hulki, password already known). Use SSH for GitHub push (HTTPS tokens expired).

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Plugin Code
- `community-master.php` — Plugin entry point, version number for ZIP
- `includes/` — All PHP classes
- `templates/` — Frontend templates
- `assets/` — CSS and JS

### API Access
- WordPress REST API: https://meintechblog.de/wp-json/wp/v2/
- Auth: Basic Auth with Application Password (user: hulki)
- GitHub: SSH push to git@github.com:meintechblog/meintechblog-community-master.git

### Requirements
- `.planning/REQUIREMENTS.md` — DEPL-01..04

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `tests/test-rest-api.sh` — Shows curl patterns for REST API usage, can inform the project seeding script
- WordPress REST API endpoints already tested in Phase 3

### Established Patterns
- Application Password auth: `curl -u "hulki:[REDACTED - credential removed]"`
- SSH for GitHub: `git remote set-url origin git@github.com:meintechblog/meintechblog-community-master.git`

### Integration Points
- `POST /wp-json/wp/v2/community_project` — Create IP-Cam Master project
- `POST /wp-json/wp/v2/media` — Upload placeholder logo
- `POST /wp-json/wp/v2/pages` — Create /community-master page
- `gh release create` or manual ZIP upload to GitHub

</code_context>

<specifics>
## Specific Ideas

- The ZIP should only contain plugin files (no .planning/, no tests/, no .git/)
- Page slug should be `community-master` to match the URL requirement
- Consider creating a simple deploy script that can be re-run for updates

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 04-deployment-launch*
*Context gathered: 2026-03-24*
