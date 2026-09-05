[//]: # (TOC: 9. Fehlersuche)

# Fehlersuche

Bei Störungen lohnt es sich, zuerst den betroffenen Bereich einzugrenzen: Anwendung, Datenbank, Mail, Queue, Browser-Laufzeit oder Webserver.

## Häufige Fehlerbilder

### Login funktioniert nicht

Prüfen Sie:

- stimmt `APP_URL`?
- stimmen Session- und Cookie-Einstellungen?
- funktioniert die Datenbank?
- ist das Benutzerkonto aktiv?

### Seiten laden, aber Speichern schlägt fehl

Prüfen Sie:

- Anwendungslog
- Datenbankverbindung
- PHP-Fehler
- fehlende Schreibrechte

### Debug- oder Entwicklerseiten sind erreichbar

Prüfen Sie:

- `APP_DEBUG` steht nicht versehentlich auf `true`
- `BOOST_ENABLED` und `BOOST_BROWSER_LOGS_WATCHER` sind nicht unbeabsichtigt aktiviert
- `IGNITION_ENABLE_RUNNABLE_SOLUTIONS` ist nicht aktiviert
- `TELESCOPE_ENABLED` ist nur für bewusst abgesicherte lokale Sitzungen aktiv
- `DUSK_ENABLED` ist nur für gezielte Browser-Tests aktiv
- Entwicklungswerkzeuge wie Dusk oder Telescope laufen nicht auf einem öffentlich erreichbaren System

### Mails kommen nicht an

Prüfen Sie:

- Mail-Konfiguration in `.env`
- SMTP-Verbindung
- SPF, DKIM, DMARC
- Queue-Worker, falls Mailversand asynchron läuft

### PDF- oder Browser-Ausgaben schlagen fehl

Prüfen Sie:

- Chromium/Chrome installiert?
- Systembibliotheken vollständig?
- Startet der Browser für den Webserver-Benutzer?
- gibt es genug temporären Speicher?

### Updates brechen ab

Prüfen Sie:

- lokaler Git-Status
- Upstream-Branch
- Composer- oder npm-Abhängigkeiten
- Migrationen
- Dateirechte

## Wichtige Protokollquellen

- Laravel-Logs in `storage/logs/`
- Webserver-Logs
- PHP-FPM- oder Prozess-Logs
- Queue- und Scheduler-Logs
- Container-Logs bei Docker

## Gute Reihenfolge bei der Fehlersuche

1. Symptom kurz beschreiben.
2. Reproduzierbarkeit prüfen.
3. Logs derselben Zeitspanne ansehen.
4. zuletzt geänderte Systemteile notieren.
5. erst dann korrigieren oder zurückrollen.
