# Phase 1: Plugin Core & Admin - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-24
**Phase:** 01-plugin-core-admin
**Areas discussed:** Beschreibungsfeld, Capabilities, Admin-Spalten, Plugin-Name

---

## Beschreibungsfeld

| Option | Description | Selected |
|--------|-------------|----------|
| Post Content (Recommended) | WordPress-Editor (Gutenberg) for description. Allows formatting, images, links. | |
| Einfaches Textfeld | Simple textarea meta field. Plaintext only, no formatting. Leaner. | ✓ |
| You decide | Claude chooses best approach | |

**User's choice:** Einfaches Textfeld
**Notes:** User prefers lean approach, no need for rich text editing for short project descriptions.

---

## Capabilities

| Option | Description | Selected |
|--------|-------------|----------|
| Nur Admin (Recommended) | capability_type = 'post'. Only admins can manage projects. Simplest solution. | |
| Admin + Editor | Custom capability_type with custom caps. Admins and editors can manage projects. | ✓ |
| You decide | Claude chooses based on use case | |

**User's choice:** Admin + Editor
**Notes:** More flexible for future use. Requires custom capabilities with map_meta_cap => true.

---

## Admin-Spalten

| Option | Description | Selected |
|--------|-------------|----------|
| Logo-Thumbnail | Small preview image of project logo | ✓ |
| GitHub-URL | Link to GitHub repository | ✓ |
| Sortierung | menu_order value for quick overview | ✓ |
| You decide | Claude chooses sensible columns | |

**User's choice:** All three: Logo-Thumbnail, GitHub-URL, Sortierung
**Notes:** User asked what "Sortierung" means — explained it's the numeric order field controlling frontend display order.

---

## Plugin-Name

| Option | Description | Selected |
|--------|-------------|----------|
| Community Master | Menu: 'Community Master', text-domain: 'community-master', slug: 'meintechblog-community-master' | ✓ |
| Community Projekte | Menu: 'Community Projekte', German name in admin | |
| You decide | Claude chooses fitting name | |

**User's choice:** Community Master
**Notes:** Consistent with GitHub repo name.

---

## Claude's Discretion

- Meta box layout and grouping
- Admin CSS for meta boxes
- Exact sanitize/escape functions per field type
- Activation/deactivation hook implementation details

## Deferred Ideas

None — discussion stayed within phase scope
