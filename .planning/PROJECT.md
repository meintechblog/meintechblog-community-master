# Community Master

## What This Is

Ein WordPress-Plugin, das Community-Projekte (GitHub-Repositories) als Kachel-Grid auf meintechblog.de/community-master darstellt. Projekte werden im WordPress-Backend verwaltet und im Frontend als ansprechende Kacheln mit Logo, Beschreibung, GitHub-Link und optionalem One-Line-Installer angezeigt. Das Plugin ist API-fähig, sodass neue Projekte auch programmatisch (z.B. durch Claude) angelegt werden können.

## Core Value

Blog-Leser können auf einen Blick alle Community-Projekte entdecken und direkt zu den GitHub-Repos navigieren — strukturiert, einheitlich und mit allen relevanten Infos auf einer Seite.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Custom Post Type "Community Project" im WordPress-Backend
- [ ] Felder pro Projekt: Name, Logo (Media Upload), Beschreibung, GitHub-Link, One-Line-Installer (optional)
- [ ] Frontend-Kachel-Grid auf einer WordPress-Seite (Shortcode oder Gutenberg Block)
- [ ] Design integriert sich nahtlos ins bestehende WordPress-Theme
- [ ] One-Line-Installer wird nur angezeigt wenn vorhanden, in einer kopierbaren Code-Box
- [ ] WordPress REST API Endpunkt zum Anlegen/Bearbeiten/Löschen von Projekten
- [ ] Erstes Projekt "IP-Cam Master" ist angelegt und sichtbar

### Out of Scope

- Automatischer GitHub-Sync (kein automatisches Polling von Repos) — Projekte werden manuell oder per API gepflegt
- Benutzerbewertungen/Kommentare auf Kacheln — WordPress-Kommentarfunktion reicht bei Bedarf
- Multi-Site-Support — nur für meintechblog.de vorgesehen
- Eigenes Styling/Dark Theme — Plugin nutzt das bestehende WordPress-Theme

## Context

- Zielseite: meintechblog.de/community-master
- Quell-Repos: Öffentliche `-master` Repos unter github.com/meintechblog
- WordPress-Zugang: REST API mit Benutzer "hulki" und Application Password
- GitHub-Zugang: SSH (HTTPS-Tokens abgelaufen)
- Erstes Showcase-Projekt: github.com/meintechblog/ip-cam-master
- Das Plugin soll so gebaut sein, dass Claude zukünftig per API neue Projekte eintragen kann ("Stell Repo X online")
- Plugin-Code lebt in eigenem Repo: github.com/meintechblog/meintechblog-community-master

## Constraints

- **Platform**: WordPress-Plugin (PHP), kompatibel mit aktuellem WordPress (6.x)
- **Deployment**: Plugin wird auf meintechblog.de installiert
- **Design**: Muss sich ins bestehende WordPress-Theme einfügen (kein eigenes Theme/Styling)
- **GitHub**: Push nur über SSH, nicht HTTPS
- **API**: WordPress REST API für programmatisches Management

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Custom Post Type statt eigene DB-Tabelle | WordPress-nativ, nutzt bestehende Admin-UI, REST API gratis | — Pending |
| Shortcode + optional Gutenberg Block | Maximale Kompatibilität mit allen Themes | — Pending |
| Eigenes GitHub-Repo für Plugin-Code | Saubere Trennung, eigene Versionierung, einfache Updates | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd:transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd:complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-03-24 after initialization*
