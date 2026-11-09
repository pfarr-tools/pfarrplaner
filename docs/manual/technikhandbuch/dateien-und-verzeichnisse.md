[//]: # (TOC: 4. Dateien und Verzeichnisse)

# Dateien und Verzeichnisse

## Repository-Root

- `planer`: zentraler Entwicklungs-, Betriebs- und Migrationsbefehl
- `compose.yaml`: lokale Docker-Compose-Umgebung
- `compose.production.yaml`: produktive Docker-Compose-Umgebung
- `Dockerfile`: PHP-/Node-Build- und Laufzeit-Images
- `backup.yaml`: nicht versionierte Backup-Konfiguration
- `scripts/`: Root-Betriebs-, Backup-, Migrations- und Testskripte
- `docs/manual/`: drei veröffentlichte Handbücher und gemeinsame Medien
- `src/`: Laravel-Anwendung als Git-Subtree

Der empfohlene Einstieg für eine neue Umgebung ist [Installation und Erstbetrieb mit Docker](installation-docker.md). Die Datei beschreibt auch, welche Daten außerhalb des Quellcodes persistent bleiben müssen.

## Anwendungsquellcode unter `src/`

- `app/`: Anwendungslogik
- `app/Http/Controllers/Api/`: API-Controller
- `app/Actions/`: fachliche Aktionen
- `app/Models/`: Domänenmodelle
- `config/`: Laufzeitkonfiguration
- `database/`: Migrationen, Factories, Seeder
- `config/manual.php`: Zuordnung von Anwendungsrouten zu Handbuchkapiteln
- `resources/js/`: Vue- und Inertia-Frontend
- `routes/`: Web-, API- und Teilrouten
- `scripts/`: Build-, Release- und Hilfsskripte
- `storage/`: Logs und lokale Laufzeitdaten der Laravel-Anwendung
- `storage/inbox/`: gemeinsamer Eingang des FTP-Dienstes für noch nicht verarbeitete Scanner-Dateien; dauerhaft gespeicherte Anhänge liegen in MinIO

## Dokumentationsrelevante Dateien

- `../docs/manual/`: gemeinsame Manual-Quelle aus Sicht von `src/`
- `scripts/build-manual-site.js`: baut alle drei Manuals als statische Site und PDF
- `scripts/deploy-manual-site.js`: deployt die gebaute Handbook-Site
- `app/Console/Commands/DevBuilder/BuildManualPages.php`: erzeugt Benutzerhandbuch-Seiten wie Versionsangaben und Lizenzen
- `../scripts/manual.sh`: Root-Dispatcher für Manual-Build, Dusk, Site und Veröffentlichung

## Betriebs- und Update-relevante Dateien

- `compose.yaml`: lokale Entwicklungsumgebung einschließlich Manual-Container
- `compose.production.yaml`: Produktionsumgebung
- `ftp_inbox`: persistentes Compose-Volume für den FTP-Eingang; nicht mit dem MinIO-Bucket verwechseln
- `planer`: zentraler Einstieg für Betrieb, Updates und Datenpflege
- `scripts/update-production.sh`: abgesicherter Produktions-Update-Ablauf

## API-relevante Dateien

- `routes/api.php`: lädt alle API-Teilrouten
- `routes/api/*.php`: Route-Definitionen
- `app/Console/Commands/GenerateOpenApiSpec.php`: erzeugt `openapi.json`
- `app/Http/Controllers/Api/OpenApiSpec.php`: zentrale OpenAPI-Tags und Serverdefinition
