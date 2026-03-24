# Requirements: Community Master

**Defined:** 2026-03-24
**Core Value:** Blog-Leser können auf einen Blick alle Community-Projekte entdecken und direkt zu den GitHub-Repos navigieren.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Plugin Foundation

- [x] **FOUND-01**: Plugin registriert Custom Post Type "community_project" mit Admin-UI
- [x] **FOUND-02**: Plugin aktiviert Featured Image Support für Projekt-Logos
- [x] **FOUND-03**: Plugin flusht Rewrite Rules nur bei Activation/Deactivation
- [x] **FOUND-04**: Plugin hat saubere Uninstall-Routine (entfernt CPT-Daten und Optionen)

### Project Fields

- [x] **FIELD-01**: Admin kann Projekt-Name eingeben (= Post Title)
- [x] **FIELD-02**: Admin kann Projekt-Beschreibung eingeben (= Post Content oder Meta)
- [x] **FIELD-03**: Admin kann Projekt-Logo hochladen (= Featured Image)
- [x] **FIELD-04**: Admin kann GitHub-URL eingeben (Meta Field, validiert auf github.com)
- [x] **FIELD-05**: Admin kann optionalen One-Line-Installer eingeben (Meta Field)
- [x] **FIELD-06**: Admin kann Projektreihenfolge festlegen (menu_order)

### Frontend Display

- [ ] **FRONT-01**: Shortcode `[community-master]` zeigt Projekt-Grid an
- [ ] **FRONT-02**: Kacheln zeigen Logo, Name, Beschreibung und GitHub-Link
- [ ] **FRONT-03**: Grid ist responsive (3 Spalten Desktop, 2 Tablet, 1 Mobile)
- [ ] **FRONT-04**: One-Line-Installer wird nur angezeigt wenn vorhanden, in kopierbarer Code-Box
- [ ] **FRONT-05**: Copy-to-Clipboard Button für One-Line-Installer mit visuellem Feedback
- [ ] **FRONT-06**: Design integriert sich ins bestehende WordPress-Theme (kein eigenes Styling)
- [ ] **FRONT-07**: Empty State zeigt hilfreiche Nachricht wenn keine Projekte existieren

### REST API

- [ ] **API-01**: Projekte können per REST API erstellt werden (POST)
- [ ] **API-02**: Projekte können per REST API bearbeitet werden (PUT/PATCH)
- [ ] **API-03**: Projekte können per REST API gelöscht werden (DELETE)
- [ ] **API-04**: Alle Custom Meta Fields sind über REST API les- und schreibbar
- [ ] **API-05**: REST API Endpunkte haben korrekte Permission Callbacks (capability-based)

### Security

- [x] **SEC-01**: Alle Meta Field Eingaben werden sanitized (sanitize_callback)
- [ ] **SEC-02**: Alle Frontend-Ausgaben werden escaped (esc_html, esc_url, esc_attr)
- [ ] **SEC-03**: One-Line-Installer Output wird sicher escaped (XSS-Schutz)
- [x] **SEC-04**: Meta Boxes verwenden Nonce-Verification
- [ ] **SEC-05**: REST API verwendet capability-based Permission Checks

### Deployment

- [ ] **DEPL-01**: Plugin ist auf meintechblog.de installiert und aktiviert
- [ ] **DEPL-02**: Seite /community-master existiert mit eingebettetem Shortcode
- [ ] **DEPL-03**: Erstes Projekt "IP-Cam Master" ist angelegt und sichtbar
- [ ] **DEPL-04**: Plugin-Code liegt im GitHub Repo meintechblog/meintechblog-community-master

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Enhanced UI

- **UI-01**: Gutenberg Block als Alternative zum Shortcode
- **UI-02**: Drag-and-Drop Sortierung im Admin
- **UI-03**: Filtermöglichkeit bei >30 Projekten

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Automatischer GitHub-Sync/Polling | Adds API complexity, caching, stale data. Manual + API reicht |
| Multiple Layouts (Masonry, Slider) | Feature bloat. Ein Grid-Layout reicht für <20 Projekte |
| Kategorie/Tag-Filterung | Bei <20 Projekten unnötig |
| Eigenes Theme/Dark Mode | Plugin nutzt Theme-Styles |
| Social Sharing Buttons | GitHub-Seite hat bereits Sharing |
| Star/Fork/Watcher Counts | GitHub API Dependency, Caching-Aufwand |
| Lightbox/Modal Popups | Cards linken direkt zu GitHub |
| Import/Export | WordPress hat Built-in Export |
| User Ratings/Kommentare | WordPress-Kommentare reichen bei Bedarf |
| Multi-Site Support | Nur für meintechblog.de |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| FOUND-01 | Phase 1 | Complete |
| FOUND-02 | Phase 1 | Complete |
| FOUND-03 | Phase 1 | Complete |
| FOUND-04 | Phase 1 | Complete |
| FIELD-01 | Phase 1 | Complete |
| FIELD-02 | Phase 1 | Complete |
| FIELD-03 | Phase 1 | Complete |
| FIELD-04 | Phase 1 | Complete |
| FIELD-05 | Phase 1 | Complete |
| FIELD-06 | Phase 1 | Complete |
| FRONT-01 | Phase 2 | Pending |
| FRONT-02 | Phase 2 | Pending |
| FRONT-03 | Phase 2 | Pending |
| FRONT-04 | Phase 2 | Pending |
| FRONT-05 | Phase 2 | Pending |
| FRONT-06 | Phase 2 | Pending |
| FRONT-07 | Phase 2 | Pending |
| API-01 | Phase 3 | Pending |
| API-02 | Phase 3 | Pending |
| API-03 | Phase 3 | Pending |
| API-04 | Phase 3 | Pending |
| API-05 | Phase 3 | Pending |
| SEC-01 | Phase 1 | Complete |
| SEC-02 | Phase 2 | Pending |
| SEC-03 | Phase 2 | Pending |
| SEC-04 | Phase 1 | Complete |
| SEC-05 | Phase 3 | Pending |
| DEPL-01 | Phase 4 | Pending |
| DEPL-02 | Phase 4 | Pending |
| DEPL-03 | Phase 4 | Pending |
| DEPL-04 | Phase 4 | Pending |

**Coverage:**
- v1 requirements: 31 total
- Mapped to phases: 31
- Unmapped: 0

---
*Requirements defined: 2026-03-24*
*Last updated: 2026-03-24 after roadmap creation*
