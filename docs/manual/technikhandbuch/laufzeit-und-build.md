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

- `./planer dev manual build` erzeugt die generierten Seiten und Lizenzseiten.
- `./planer dev manual dusk` führt die Browser-Screenshot-Tests aus.
- `./planer dev manual site` baut die statische Website und die drei PDFs.
- `./planer dev manual all` verbindet Screenshot-, Generierungs- und Site-Build.
- `./planer dev manual deploy --dry-run` prüft die Veröffentlichung ohne Übertragung.
- `./planer dev manual deploy` veröffentlicht die fertige Site per Rsync.
- `./planer dev manual source-publish` veröffentlicht die Quellen in das konfigurierte Quell-Repository.

Dieselben Aktionen können als eigenständige Entwicklungsbefehle mit Doppelpunkt
aufgerufen werden, zum Beispiel `./planer dev manual:build` oder
`./planer dev manual:deploy --dry-run`. Die verschachtelte Schreibweise
`./planer dev manual build` bleibt weiterhin gültig.

Der Site- und PDF-Build läuft im Dev-Compose-Workflow im dedizierten
`manual`-Container. Dadurch sind MkDocs, Material for MkDocs und WeasyPrint
unabhängig von der Python-Installation des Hostsystems. `./planer dev manual
site` verwendet diesen Container automatisch.

Die zugrunde liegenden Laravel-, Node- und JavaScript-Befehle können weiterhin
direkt verwendet werden. Der Dev-Einstieg `./planer dev manual` setzt jedoch
den gemeinsamen Manual-Pfad `docs/manual` korrekt und führt PHP-Schritte im
Anwendungscontainer aus.

Der Handbook-Build erzeugt jetzt:

- `benutzerhandbuch/`
- `administratorhandbuch/`
- `technikhandbuch/`
- eine gemeinsame Landing-Page unter `https://handbuch.pfarrplaner.de/`

## Screenshot-Workflow

Benutzerhandbuch-Screenshots werden über Dusk-Tests unter `tests/Browser/Manual/` erzeugt. Der kombinierte Build mit temporärem Laravel-Server läuft über `scripts/build-manual-with-server.js`.

## Browser-Tests (Dusk)

Für lokale Browser-Tests steht `npm run test:dusk` zur Verfügung. Der Aufruf startet `php artisan dusk:run --compact` mit der kompakten Collision-Ausgabe im Stil von `php artisan test --compact`. Während längerer stiller Phasen gibt der Runner zusätzlich regelmäßige Statusmeldungen aus, damit ein langsamer Browser-Test nicht wie ein Hänger aussieht.

Aus dem Root-Repository werden die Manual-Screenshot-Tests bevorzugt über
`./planer dev manual dusk` beziehungsweise als Teil von `./planer dev manual all`
gestartet. Die direkten npm- und Artisan-Befehle gelten für einen Lauf im
`src/`-Checkout beziehungsweise innerhalb des App-Containers.

Zusätzlich gibt es gezielte Varianten für die lokale Fehlersuche:

- `npm run test:dusk:compact` verwendet ebenfalls die kompakte Collision-Ausgabe und erlaubt zusätzliche Optionen wie `--stop-on-failure`.
- `npm run test:dusk:debug` zeigt die jeweils laufenden PHPUnit-Browser-Tests direkt im Terminal an.
- `npm run test:dusk:testdox` verwendet die PHPUnit-TestDox-Ausgabe.

Der Dusk-Command baut das Frontend standardmäßig genau einmal selbst, setzt die SQLite-Testdatenbank zurück und startet bei Bedarf einen lokalen Laravel-Testserver. Für schnellere Wiederholungsläufe stehen unter anderem diese Optionen zur Verfügung:

Wenn keine `.env.dusk` vorhanden ist, erzeugt der Runner dafür eine temporäre,
isolierte Dusk-Umgebung mit einer dateibasierten Session und einer eigenen
SQLite-Datei. Der lokale Testserver läuft mit mehreren PHP-CLI-Workern und
ohne ungeleitete Ausgabepipes; dadurch bleiben Login-Sessions und lange
Screenshot-Läufe auch bei vielen Manual-Testklassen stabil. Jede Manual-
Testmethode erhält außerdem eine neue Browser-Sitzung.

- `php artisan dusk:run --without-build` verwendet den vorhandenen Vite-Build erneut.
- `php artisan dusk:run --stop-on-failure` bricht nach dem ersten fehlschlagenden Browser-Test ab.
- `php artisan dusk:run --without-server` verwendet einen bereits laufenden lokalen Server mit passender `APP_URL`.

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
