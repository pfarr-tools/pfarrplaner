[//]: # (TOC: 4. Laufzeit, Build und Hintergrundprozesse)

# Laufzeit, Build und Hintergrundprozesse

## PHP-Laufzeit

Pfarrplaner setzt derzeit PHP `^8.4` voraus. Die Anwendung läuft klassisch als Laravel-Webanwendung und kann zusätzlich mit Octane-/RoadRunner-Bausteinen erweitert werden.

## Frontend-Build

Das Frontend wird mit Vite gebaut. Relevante Schritte:

- `npm install`
- `npm run build`
- lokal optional `npm run dev`

## Dokumentations-Build

Für die Handbook-Site gibt es eine eigene Pipeline:

- `php artisan build:manual`
- `node scripts/build-manual-site.js`
- optional `node scripts/deploy-manual-site.js`

Der Handbook-Build erzeugt jetzt:

- `benutzerhandbuch/`
- `administratorhandbuch/`
- `technikhandbuch/`
- eine gemeinsame Landing-Page unter `https://handbuch.pfarrplaner.de/`

## Screenshot-Workflow

Benutzerhandbuch-Screenshots werden über Dusk-Tests unter `tests/Browser/Manual/` erzeugt. Der kombinierte Build mit temporärem Laravel-Server läuft über `scripts/build-manual-with-server.js`.

Entwickler-Hilfsrouten werden in Pfarrplaner bewusst restriktiv behandelt:

- `APP_DEBUG` fällt ohne explizite Umgebungsvariable auf `false` zurück.
- Laravel Boost ist standardmäßig deaktiviert und muss über `BOOST_ENABLED=true` bewusst eingeschaltet werden.
- Ignition-Housekeeping und runnable solutions bleiben standardmäßig deaktiviert und werden nur bei expliziter Freigabe über Umgebungsvariablen aktiviert.
- Telescope bleibt standardmäßig deaktiviert und wird nur über `TELESCOPE_ENABLED=true` bewusst freigeschaltet.
- Dusk-Hilfsrouten werden zusätzlich durch `DUSK_ENABLED=true` abgesichert und sind nur für gezielte Browser-Testläufe vorgesehen.

## Scheduler und Queue

Technisch gehören diese Prozesse fest zur Laufzeit:

- `schedule:run`
- Queue-Worker
- Neustart der Worker nach Code- oder Konfigurationswechsel

## Browser-Laufzeit

Da Pfarrplaner Puppeteer und Browsershot nutzt, ist ein funktionierender Chromium-Stack Teil der Laufzeit. Fehler in diesem Bereich zeigen sich häufig erst bei PDF-, Screenshot- oder Exportfunktionen.
