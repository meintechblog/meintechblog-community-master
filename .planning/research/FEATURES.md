# Features Research: Community Master WordPress Plugin

**Domain:** WordPress plugin for displaying GitHub community projects as a card grid
**Researched:** 2026-03-24
**Confidence:** HIGH (well-established WordPress plugin patterns, multiple reference plugins analyzed)

## Landscape Context

The WordPress plugin ecosystem has mature patterns for card/grid/portfolio displays. Key reference plugins analyzed:

- **Projects Manager for GitHub** -- CPT-based GitHub repo importer with README rendering
- **Embed Repo for GitHub** -- Gutenberg block for embedding repos with masonry layout
- **CardCrafter** -- Generic data-driven card grids with field mapping and shortcode
- **GS Team Members** -- Team card grids with 50+ templates, shortcode + block support
- **Visual Portfolio** -- Masonry/grid portfolio with AJAX filtering and lazy loading
- **Grid Kit Portfolio** -- 1000+ animations, social sharing, multiple layout schemes

Community Master is narrower in scope than all of these. That is a strength -- it does one thing (display community GitHub projects) and does it well. The feature set should stay lean.

## Table Stakes

Features users expect. Missing = plugin feels broken or amateur.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Custom Post Type with admin UI | Every portfolio/showcase plugin uses CPTs. WordPress admins expect familiar post editing. | Low | `register_post_type()` with `show_in_rest => true` gives admin UI + REST API for free |
| Project fields: Name, Description, Logo, GitHub URL | Core data model per PROJECT.md requirements. Users cannot meaningfully display projects without these. | Low | Name = post_title, Description = post_content or meta, Logo = featured image, GitHub URL = meta field |
| Responsive card grid frontend | Every competing plugin offers responsive grid. Non-responsive = unusable on mobile. | Medium | CSS Grid or Flexbox. 3 columns desktop, 2 tablet, 1 mobile. No JS framework needed. |
| Shortcode for embedding | `[community-master]` shortcode is the universal WordPress embedding pattern. Works in Classic Editor, Gutenberg, page builders. | Low | Single shortcode, no attributes needed for MVP. Maximum compatibility. |
| GitHub link on each card | The entire point is linking to repos. Every GitHub embed plugin links to the repo prominently. | Low | External link icon or button on card |
| Responsive images / proper thumbnails | Cards with broken or oversized images feel broken. WordPress has built-in thumbnail support. | Low | Use `add_image_size()` for card-optimized crop. Let WordPress handle srcset. |
| Clean uninstall | WordPress plugin guidelines require clean removal. Users expect no leftover data pollution. | Low | `uninstall.php` to remove CPT posts, meta, and options. Ask before deleting content. |
| WordPress 6.x compatibility | Users run current WordPress. Incompatibility = instant uninstall. | Low | Use current APIs, test with latest WP |

## Differentiators

Features that set Community Master apart. Not expected in a basic showcase plugin, but valuable for the specific use case.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| One-Line Installer with copy-to-clipboard | Unique to this plugin. No portfolio plugin offers copyable install commands. Lets readers install projects immediately. | Low | Conditional display (only when field populated). `navigator.clipboard.writeText()` with visual feedback. Minimal JS. |
| REST API for programmatic project management | Enables Claude/automation to create projects via API ("Stell Repo X online"). Most portfolio plugins are admin-only. | Low | CPT with `show_in_rest => true` provides full CRUD for free. Custom meta fields need `register_meta()` with `show_in_rest`. Application Password auth already available on meintechblog.de. |
| Card ordering / manual sort | Let admin control which projects appear first (e.g., newest or most important). Most basic portfolio plugins lack this. | Low | Use `menu_order` field on CPT + `orderby` in query. Drag-and-drop is nice but not needed -- numeric order field suffices. |
| Empty state handling | When no projects exist yet, show a meaningful message instead of blank page. Small touch, rarely done well. | Low | Check post count in shortcode, render helpful message |
| GitHub URL validation | Prevent accidental non-GitHub URLs in the link field. No portfolio plugin validates URLs domain-specifically. | Low | Simple PHP validation on save: must start with `https://github.com/` |

## Anti-Features

Features to explicitly NOT build. Each would add complexity without serving the core use case.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| Automatic GitHub sync / polling | PROJECT.md explicitly scopes this out. Adds API rate limiting complexity, cron job maintenance, and stale data issues. Projects Manager for GitHub does this and creates maintenance burden. | Manual entry via admin + REST API for automation. Claude can push updates when needed. |
| Multiple layout options (masonry, slider, carousel) | Feature bloat. CardCrafter offers 3 layouts, Visual Portfolio offers 6. Each adds CSS/JS complexity and testing surface. Community Master needs exactly one grid layout. | Single responsive grid. Consistent, predictable, zero configuration. |
| Category/tag filtering | With 5-20 community projects, filtering is pointless overhead. Portfolio plugins add this for 50-200+ item catalogs. | Simple alphabetical or manual ordering. If filtering becomes needed at 50+ projects, reconsider then. |
| Gutenberg block (for MVP) | Shortcode works everywhere -- Classic Editor, Gutenberg, page builders, widgets. Block requires `@wordpress/scripts` build toolchain, React JSX, and doubles the codebase. | Shortcode first. Gutenberg block is a valid Phase 2 feature if demand exists. |
| Lightbox / modal popups | Portfolio plugins use lightboxes for image galleries. Community Master cards link to GitHub -- there is nothing to "expand" into. | Direct link to GitHub repo. Clean, fast, no JS overhead. |
| Social sharing buttons | Grid Kit adds share buttons on cards. Irrelevant for developer tool repositories. Nobody shares individual repo cards on social media. | GitHub repo page already has sharing. Don't duplicate. |
| Star/fork/watcher counts from GitHub API | Projects Manager for GitHub imports these. Adds GitHub API dependency, caching needs, and stale data. For a small community showcase, vanity metrics add noise. | Keep it simple. Users click through to GitHub for repo stats. |
| Custom CSS/theme builder | Plugins like GS Team Members offer 50+ themes and color pickers. This is a single-site plugin for meintechblog.de. | Inherit theme styles. One clean card design. Admin doesn't need color pickers. |
| Pagination | With fewer than 20 projects expected, pagination fragments the experience unnecessarily. | Show all projects. If project count exceeds 30-40, add simple pagination then. |
| Import/export functionality | Multi-site and migration features are out of scope per PROJECT.md. | WordPress has built-in CPT export via Tools > Export. Don't reinvent. |
| User ratings / comments on cards | PROJECT.md explicitly excludes this. WordPress comment system exists if ever needed. | Standard WordPress comments on single project pages if desired (inherent in CPTs). |

## Feature Dependencies

```
Custom Post Type ──> Admin UI (free with CPT registration)
                ──> REST API (free with show_in_rest)
                ──> Shortcode (queries CPT posts)
                ──> Frontend Grid (renders CPT posts as cards)

Logo field ──> Featured Image support (add_post_type_support)
           ──> Thumbnail size registration (add_image_size)

One-Line Installer ──> Meta field on CPT
                   ──> Conditional rendering in card template
                   ──> Copy-to-clipboard JS (tiny, inline-able)

REST API ──> CPT with show_in_rest
         ──> register_meta for custom fields (GitHub URL, installer)
         ──> Application Password on WordPress (already configured)

Shortcode ──> CPT query
          ──> Card template (HTML/CSS)
          ──> Responsive grid CSS
```

## MVP Recommendation

Build in this order:

1. **Custom Post Type registration** -- unlocks admin UI, REST API, and query foundation
2. **Meta fields: GitHub URL + One-Line Installer** -- registered with REST API visibility
3. **Card template + responsive grid CSS** -- the frontend output
4. **Shortcode** -- connects CPT to any page
5. **Copy-to-clipboard for installer** -- the key differentiator
6. **First project "IP-Cam Master"** -- validates the full stack

Defer:
- **Gutenberg block**: Only if shortcode proves insufficient. Adds build toolchain complexity.
- **Card ordering UI**: Numeric `menu_order` field works. Drag-and-drop can come later.
- **Pagination**: Not needed under 30 projects.

## Sources

- [Projects Manager for GitHub](https://wordpress.org/plugins/projects-manager-for-github/) -- closest competitor, CPT-based GitHub importer
- [Embed Repo for GitHub](https://wordpress.org/plugins/embed-github/) -- Gutenberg block approach
- [CardCrafter](https://wordpress.org/plugins/cardcrafter-data-grids/) -- data-driven card grid with field mapping
- [GS Team Members](https://wordpress.org/plugins/gs-team-members/) -- team card grid with 50+ templates
- [Visual Portfolio](https://wordpress.org/plugins/visual-portfolio/) -- portfolio grid with AJAX filtering
- [WordPress REST API Authentication Handbook](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)
- [Adding REST API Support for Custom Content Types](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/)
- [Copy Anything to Clipboard Plugin](https://wordpress.org/plugins/copy-the-code/) -- reference for clipboard UX patterns
