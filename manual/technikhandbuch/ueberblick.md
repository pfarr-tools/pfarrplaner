[//]: # (TOC: 1. Einführung)

# Einführung

Pfarrplaner ist eine Laravel-Anwendung mit Inertia.js und Vue. Das System verbindet klassische Serverlogik, ein modernes Single-Page-Frontend, dokumentenorientierte Ausgaben und eine gewachsene API-Landschaft für Kalender-, Liturgie- und Verwaltungsfunktionen.

## Technischer Kern

- Backend: Laravel
- Frontend: Inertia.js mit Vue
- Build: Vite
- Authentifizierung: Laravel-Mechanismen und API-Schutz über `auth:api` bzw. Sanctum-Bausteine im Projekt
- Dokumente und Exporte: PDF-, Office- und HTML-Ausgaben
- Browser-Automatisierung: Puppeteer und Browsershot

## Wichtige Betriebsmodi

- klassische PHP-Webanwendung hinter Apache oder Nginx
- optional erweiterte Laufzeit mit Octane/RoadRunner
- lokale Entwicklungsumgebungen
- Docker-Compose-Umgebungen

## Drei technische Sichtweisen

1. **Domänenlogik**: Gottesdienste, Kasualien, Benutzer, Orte, Urlaub, Liturgie.
2. **Betrieb**: `.env`, Datenbank, Storage, Queue, Scheduler, Logs, Chromium.
3. **Schnittstellen**: Web-Routen, API-Routen, OpenAPI, Exporte, externe Integrationen.
