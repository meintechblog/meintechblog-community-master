# Roadmap: Community Master

## Overview

Community Master is a WordPress plugin that showcases community projects as a tile grid on meintechblog.de. The roadmap delivers the plugin in four phases: first the data model and admin experience, then the public-facing display, then the REST API for programmatic access (so Claude can manage projects), and finally deployment to production with the first showcase project.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Plugin Core & Admin** - CPT registration, meta fields, and complete admin editing experience
- [ ] **Phase 2: Frontend Display** - Shortcode-driven responsive tile grid with theme integration
- [ ] **Phase 3: REST API** - Programmatic CRUD endpoints for external project management
- [ ] **Phase 4: Deployment & Launch** - Install on meintechblog.de with first showcase project

## Phase Details

### Phase 1: Plugin Core & Admin
**Goal**: Admins can create, edit, and manage community projects with all fields in the WordPress backend
**Depends on**: Nothing (first phase)
**Requirements**: FOUND-01, FOUND-02, FOUND-03, FOUND-04, FIELD-01, FIELD-02, FIELD-03, FIELD-04, FIELD-05, FIELD-06, SEC-01, SEC-04
**Success Criteria** (what must be TRUE):
  1. Admin can activate the plugin and see "Community Projects" in the WordPress admin menu
  2. Admin can create a new project with name, description, logo, GitHub URL, and optional one-line installer
  3. Admin can reorder projects via menu_order field
  4. All meta field inputs are sanitized on save and meta box forms use nonce verification
  5. Plugin deactivation and uninstall cleanly remove rewrite rules and CPT data
**Plans:** 2 plans

Plans:
- [ ] 01-01-PLAN.md — Plugin bootstrap, CPT registration, capabilities, meta fields, and lifecycle hooks
- [ ] 01-02-PLAN.md — Meta boxes for field editing and custom admin columns for list table

### Phase 2: Frontend Display
**Goal**: Visitors can browse all community projects as a visually appealing tile grid on any WordPress page
**Depends on**: Phase 1
**Requirements**: FRONT-01, FRONT-02, FRONT-03, FRONT-04, FRONT-05, FRONT-06, FRONT-07, SEC-02, SEC-03
**Success Criteria** (what must be TRUE):
  1. Adding `[community-master]` shortcode to a page renders a grid of project tiles with logo, name, description, and GitHub link
  2. Grid is responsive: 3 columns on desktop, 2 on tablet, 1 on mobile
  3. One-line installer appears only when set, in a copyable code box with a copy button that gives visual feedback
  4. Page with no projects shows a helpful empty state message
  5. All output is properly escaped against XSS (esc_html, esc_url, esc_attr)
**Plans**: TBD
**UI hint**: yes

### Phase 3: REST API
**Goal**: External clients (especially Claude) can create, update, and delete community projects programmatically
**Depends on**: Phase 1
**Requirements**: API-01, API-02, API-03, API-04, API-05, SEC-05
**Success Criteria** (what must be TRUE):
  1. A POST request with valid Application Password credentials creates a new project with all custom fields
  2. PUT/PATCH requests update existing project fields including meta data
  3. DELETE requests remove a project
  4. Unauthenticated or unauthorized requests are rejected with appropriate HTTP status codes
**Plans**: TBD

### Phase 4: Deployment & Launch
**Goal**: Plugin is live on meintechblog.de with IP-Cam Master as the first visible community project
**Depends on**: Phase 1, Phase 2, Phase 3
**Requirements**: DEPL-01, DEPL-02, DEPL-03, DEPL-04
**Success Criteria** (what must be TRUE):
  1. Plugin is installed and activated on meintechblog.de
  2. meintechblog.de/community-master displays the project grid with the shortcode
  3. IP-Cam Master project is created and visible with logo, description, GitHub link, and installer command
  4. Plugin source code is pushed to github.com/meintechblog/meintechblog-community-master
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 -> 2 -> 3 -> 4

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Plugin Core & Admin | 0/2 | Planning complete | - |
| 2. Frontend Display | 0/0 | Not started | - |
| 3. REST API | 0/0 | Not started | - |
| 4. Deployment & Launch | 0/0 | Not started | - |
