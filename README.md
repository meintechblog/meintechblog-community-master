# Community-Master

WordPress-Plugin zur Verwaltung und Darstellung von Community-Projekten. Entwickelt für [meintechblog.de/community-master](https://meintechblog.de/community-master/).

## Features

### Frontend
- **Projekt-Kacheln** — Jedes Projekt als Card mit Logo, Beschreibung, GitHub-Link und optionalem One-Line-Installer
- **Deep-Links** — `/community-master/<slug>/` zeigt eine Einzelansicht des Projekts (`<slug>` = `post_name`); unbekannte Slugs liefern HTTP 404 mit "Alle Projekte"-Back-Link
- **Klickbare Kacheln** — Projekt-Titel und Logo im Grid verlinken auf die Einzelansicht
- **Instant-Suche** — Filtert Projekte live beim Tippen
- **Sortierung** — Toggle-Button zwischen "Neueste zuerst" und "Name (A–Z)"
- **Copy-to-Clipboard** — One-Line-Installer mit einem Klick kopieren
- **Blogpost-Verknüpfung** — Aufklappbare Vorschau mit Coverbild und Textauszug (bis zum `<!--more-->`-Tag)
- **Tag-Badges** — Farbige Badges für Kategorien (z.B. Proxmox, WordPress)
- **Responsive** — Optimiert für Desktop und Mobile

### Backend (WordPress Admin)
- **Custom Post Type** — Eigener Bereich "Community Master" im Admin-Menü
- **Gutenberg-Editor** — Voller Block-Editor für Projektbeschreibungen mit Bildern und Formatierung
- **Project Details Meta Box** — GitHub URL (validiert auf github.com), One-Line-Installer, Tag-Checkboxen
- **Blogpost-Verknüpfung** — Suchfeld mit AJAX-Autocomplete + Quick-Add der letzten 10 Blogposts
- **Mehrere Blogposts** pro Projekt möglich, chronologisch sortiert
- **Admin-Spalten** — Logo, GitHub URL in der Übersichtsliste
- **Direktlink** — "Seite anzeigen ↗" im Admin-Menü, Edit-Stift auf jeder Kachel (nur für Admins)

### REST API
- Projekte per API erstellen, bearbeiten, löschen
- Alle Felder (GitHub URL, Installer, Tags, Blogpost-IDs) über REST les- und schreibbar
- Authentifizierung via WordPress Application Passwords
- GitHub URL Validierung auch bei API-Zugriffen

### Self-Update (Plugin aktualisiert sich selbst von GitHub)
- `GET /community-master/v1/update-check` — meldet installed / latest / update_available
- `POST /community-master/v1/self-update` — lädt das neueste GitHub-Release-ZIP, installiert via Core `Plugin_Upgrader` (Maintenance-Mode, Rollback bei Fehler), aktiviert, flushed Rewrite-Rules
- Optional `{ "version": "vX.Y.Z" }` für Target-Release
- Auth: Capability `update_plugins` (Application Password reicht)
- Schutz: Host-Allowlist gegen Nicht-GitHub-URLs + Transient-Lock gegen parallele Upgrades

### Daten-Recovery (aus UpdraftPlus-Backups)
- `GET /community-master/v1/restore/backups` — listet vorhandene `-db.gz`-Backups in `wp-content/updraft/`
- `POST /community-master/v1/restore/community-projects` — extrahiert `community_project`-Posts + Meta aus dem neuesten Backup und legt sie neu an (idempotent: existierende Slugs werden übersprungen)
- Unterstützt `{ "dry_run": true }` für Vorschau ohne DB-Schreibvorgang
- Auth: Capability `manage_options`

### Sichere Deinstallation
- `uninstall.php` löscht **standardmäßig keine** Projekt-Daten — nur Capabilities werden entfernt
- Opt-in: `update_option('community_master_delete_data_on_uninstall', '1')` aktiviert harte Löschung
- Verhindert Datenverlust beim Löschen von Plugin-Duplikaten (gleiche `uninstall.php` wie aktive Kopie)

### Sicherheit
- Input-Sanitization auf allen Feldern (`sanitize_text_field`, `esc_url_raw`, `wp_kses_post`)
- Output-Escaping auf allen Frontend-Ausgaben (`esc_html`, `esc_url`, `esc_attr`)
- Nonce-Verification auf Admin-Formularen
- Capability-basierte Zugriffskontrolle (Admin + Editor)
- XSS-Schutz beim One-Line-Installer (Shell-Befehle sicher escaped)

## Installation

### Per ZIP-Upload (empfohlen)

1. [Neuestes Release herunterladen](https://github.com/meintechblog/meintechblog-community-master/releases/latest)
2. WordPress Admin → Plugins → Neu hinzufügen → ZIP hochladen
3. Plugin aktivieren

### Per Git

```bash
cd /pfad/zu/wp-content/plugins/
git clone git@github.com:meintechblog/meintechblog-community-master.git community-master
```

Plugin im WordPress Admin aktivieren.

## Einrichtung

1. **Plugin aktivieren** — "Community Master" erscheint im Admin-Menü
2. **Seite erstellen** — Neue WordPress-Seite anlegen, Shortcode `[community-master]` einfügen
3. **Projekte anlegen** — Unter Community Master → Neu hinzufügen

### Shortcode

```
[community-master]
```

Zeigt alle veröffentlichten Projekte als durchsuchbare, sortierbare Kachelliste an. Intro-Text kann als normaler Absatz über dem Shortcode auf der Seite eingegeben werden.

## Felder pro Projekt

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| **Titel** | Post Title | Projektname (z.B. "IP-Cam-Master") |
| **Beschreibung** | Post Content (Gutenberg) | Ausführliche Beschreibung mit Formatierung und Bildern |
| **Beitragsbild** | Featured Image | Projekt-Logo/Icon (wird 1:1 als 160x160 angezeigt) |
| **GitHub URL** | Meta Field | Link zum GitHub-Repository (muss mit `https://github.com/` beginnen) |
| **One-Line-Installer** | Meta Field | Installationsbefehl (optional, wird in kopierbarer Code-Box angezeigt) |
| **Tags** | Meta Fields | Checkboxen für Badges (Proxmox, WordPress) |
| **Blogposts** | Meta Field (Array) | Verknüpfte Blogartikel mit aufklappbarer Vorschau |

## REST API

Projekte verwalten per HTTP — ideal für Automatisierung mit KI-Assistenten.

### Projekt erstellen

```bash
curl -u "user:app-password" \
  -X POST "https://example.com/wp-json/wp/v2/community_project" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mein Projekt",
    "status": "publish",
    "content": "Projektbeschreibung...",
    "community_master_github_url": "https://github.com/org/repo",
    "community_master_installer": "curl -fsSL https://example.com/install.sh | bash",
    "community_master_tags": ["proxmox"]
  }'
```

### Blogposts verknüpfen

```bash
curl -u "user:app-password" \
  -X POST "https://example.com/wp-json/wp/v2/community_project/123" \
  -H "Content-Type: application/json" \
  -d '{"community_master_blogpost_ids": [456, 789]}'
```

### Verfügbare REST-Felder

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| `community_master_github_url` | string | GitHub Repository URL |
| `community_master_installer` | string | One-Line-Installer Befehl |
| `community_master_tags` | string[] | Aktive Tags (`"proxmox"`, `"wordpress"`) |
| `community_master_blogpost_ids` | integer[] | IDs verknüpfter Blogposts |
| `menu_order` | integer | Sortierreihenfolge |

## Plugin-Eigene REST-Endpoints

Namespace: `community-master/v1`. Alle Endpoints erfordern WordPress-Authentifizierung (Application Password empfohlen).

### Update-Check

```bash
curl -u "user:app-password" \
  "https://example.com/wp-json/community-master/v1/update-check"
# → { "installed": "1.4.6", "latest": "1.4.6", "update_available": false, ... }
```

### Self-Update

```bash
curl -u "user:app-password" -X POST \
  "https://example.com/wp-json/community-master/v1/self-update"
# → { "success": true, "old_version": "1.4.5", "new_version": "1.4.6", "messages": [...] }
```

### Backup-Liste

```bash
curl -u "user:app-password" \
  "https://example.com/wp-json/community-master/v1/restore/backups"
# → { "backups": [{ "filename": "...", "size": ..., "modified": "...", "encrypted": false }, ...] }
```

### Community-Projekte aus Backup wiederherstellen

```bash
# Dry-run (zeigt was wiederhergestellt würde)
curl -u "user:app-password" -X POST \
  -H "Content-Type: application/json" \
  -d '{"dry_run": true}' \
  "https://example.com/wp-json/community-master/v1/restore/community-projects"

# Echter Restore aus dem neuesten Backup
curl -u "user:app-password" -X POST \
  "https://example.com/wp-json/community-master/v1/restore/community-projects"
```

## URL-Struktur

| URL | Inhalt |
|-----|--------|
| `/community-master/` | Grid aller veröffentlichten Projekte |
| `/community-master/<slug>/` | Einzelansicht eines Projekts (`<slug>` = `post_name`) |
| `/community-master/<slug>/` (unbekannt) | HTTP 404 mit "Alle Projekte"-Back-Link |

## Tech Stack

- **PHP 7.4+** — Keine externen Dependencies, nur WordPress Core APIs
- **WordPress 6.6+** — Custom Post Type, Meta Fields, REST API, Gutenberg
- **Vanilla CSS** — CSS Grid, keine CSS-Frameworks
- **Vanilla JS** — Clipboard API, DOM-Filterung, kein jQuery im Frontend

## Lizenz

MIT
