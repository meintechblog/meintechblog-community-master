# Phase 4: Deployment & Launch - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.

**Date:** 2026-03-24
**Phase:** 04-deployment-launch
**Areas discussed:** Plugin-Upload, IP-Cam Master, GitHub Repo

---

## Plugin-Upload

| Option | Description | Selected |
|--------|-------------|----------|
| REST API Upload | Plugin als ZIP über WordPress REST API hochladen | |
| WP-Admin manuell | User lädt selbst über WP-Admin hoch | |
| Other | User wants ZIP for GitHub release + Claude installs pragmatically | ✓ |

**User's choice:** ZIP-File erstellen das im GitHub-Repo als Release verfügbar ist. Claude installiert pragmatisch.
**Notes:** ZIP muss regulär installierbar und updatebar sein.

---

## IP-Cam Master

**Data from GitHub API:**
- Name: IP-Cam Master
- Description: IP Camera Management & Monitoring Tool
- URL: https://github.com/meintechblog/ip-cam-master
- Installer: `curl -fsSL https://raw.githubusercontent.com/meintechblog/ip-cam-master/main/install.sh | bash`
- Logo: Kein Logo im Repo → Platzhalter-Icon verwenden

| Option | Description | Selected |
|--------|-------------|----------|
| Ohne Logo starten | Projekt ohne Logo, später nachliefern | |
| Platzhalter nutzen | Generisches Kamera-Icon als Platzhalter | ✓ |

---

## GitHub Repo

| Option | Description | Selected |
|--------|-------------|----------|
| Ja, erstellen | Repo via gh CLI erstellen und Code pushen (SSH) | ✓ |
| Ich mache das | User erstellt Repo selbst | |

**User's choice:** Claude erstellt das Repo und pusht den Code.

## Deferred Ideas

None
