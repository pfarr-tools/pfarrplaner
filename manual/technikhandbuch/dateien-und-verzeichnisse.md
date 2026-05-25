[//]: # (TOC: 3. Dateien und Verzeichnisse)

# Dateien und Verzeichnisse

## Besonders wichtige Verzeichnisse

- `app/`: Anwendungslogik
- `app/Http/Controllers/Api/`: API-Controller
- `app/Actions/`: fachliche Aktionen
- `app/Models/`: Domänenmodelle
- `config/`: Laufzeitkonfiguration
- `database/`: Migrationen, Factories, Seeder
- `manual/`: Benutzerhandbuch
- `manual/administratorhandbuch/`: Administratorhandbuch
- `manual/technikhandbuch/`: Technisches Handbuch
- `resources/js/`: Vue- und Inertia-Frontend
- `routes/`: Web-, API- und Teilrouten
- `scripts/`: Build-, Release- und Hilfsskripte
- `storage/`: Logs, temporäre Dateien, Anwendungsdaten

## Dokumentationsrelevante Dateien

- `config/manual.php`: Zuordnung von Anwendungsrouten zu Handbuchkapiteln
- `scripts/build-manual-site.js`: baut alle drei Manuals als statische Site und PDF
- `scripts/deploy-manual-site.js`: deployt die gebaute Handbook-Site
- `app/Console/Commands/DevBuilder/BuildManualPages.php`: erzeugt Benutzerhandbuch-Seiten wie Versionsangaben und Lizenzen

## Installations- und Update-relevante Dateien

- `app/Console/Commands/InstallCommand.php`: neuer CLI-Installer `pfarrplaner:install`
- `app/Console/Commands/Install/InstallUpdates.php`: Laravel-Einstiegspunkt für Updates
- `scripts/install-updates.js`: Update-Ablauf

## API-relevante Dateien

- `routes/api.php`: lädt alle API-Teilrouten
- `routes/api/*.php`: Route-Definitionen
- `app/Console/Commands/GenerateOpenApiSpec.php`: erzeugt `openapi.json`
- `app/Http/Controllers/Api/OpenApiSpec.php`: zentrale OpenAPI-Tags und Serverdefinition
