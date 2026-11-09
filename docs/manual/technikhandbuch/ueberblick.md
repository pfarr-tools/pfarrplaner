[//]: # (TOC: 1. Einführung)

# Einführung

Pfarrplaner ist eine Laravel-Anwendung mit Inertia.js und Vue, die als Docker-Stack betrieben wird. Das System verbindet klassische Serverlogik, ein modernes Single-Page-Frontend, dokumentenorientierte Ausgaben und eine API-Landschaft für Kalender-, Liturgie- und Verwaltungsfunktionen.

## Technischer Kern

- Backend: Laravel
- Frontend: Inertia.js mit Vue
- Build: Vite
- Authentifizierung: Laravel-Mechanismen und API-Schutz über `auth:api` bzw. Sanctum-Bausteine im Projekt
- Dokumente und Exporte: PDF-, Office- und HTML-Ausgaben
- Browser-Automatisierung: Puppeteer und Browsershot

## Wichtige Betriebsmodi

- Docker-Compose-Entwicklung mit PHP-FPM, Caddy und Vite
- Docker-Compose-Produktion mit Octane/Swoole und Caddy
- `compose.yaml` für Entwicklung, Tests und den Manual-Build
- `compose.production.yaml` für den produktiven Betrieb

Die Installation beginnt mit dem [ausführlichen Docker-Ablauf](installation-docker.md). Alle betrieblichen Aktionen werden über `./planer` im Repository-Root geführt; direkte Compose- oder Artisan-Aufrufe sind Sonderfälle für Diagnose und Entwicklung.

## Drei technische Sichtweisen

1. **Domänenlogik**: Gottesdienste, Kasualien, Benutzer, Orte, Urlaub, Liturgie.
2. **Betrieb**: Root-`.env`, MariaDB, Redis, MinIO, Horizon, Scheduler, Logs, Chromium und optional Postfix.
3. **Schnittstellen**: Web-Routen, API-Routen, OpenAPI, Exporte, externe Integrationen.
