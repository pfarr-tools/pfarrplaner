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
- Status und Logs des optionalen Produktionsdienstes `postfix`
- `./planer exec app php artisan queue:failed` und Horizon
- SMTP-Verbindung beziehungsweise Relay-Zugang
- SPF, DKIM, DMARC
- öffentliche DNS- und PTR/rDNS-Einträge bei direkter MX-Zustellung

Eine ausführliche Prüfreihenfolge und der anwendungsseitige Rollback stehen im
[Mail-Betriebskapitel](mail.md).

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

- Laravel-Logs in `src/storage/logs/` beziehungsweise im App-Container
- Webserver-Logs
- PHP-FPM- oder Prozess-Logs
- Queue- und Scheduler-Logs
- Container-Logs bei Docker

## Gute Reihenfolge bei der Fehlersuche

1. Symptom kurz beschreiben.
2. Reproduzierbarkeit prüfen.
3. Logs derselben Zeitspanne ansehen.
4. zuletzt geänderte Systemteile notieren; bei Docker zusätzlich `./planer status`
   und die relevanten `./planer logs` prüfen.
5. erst dann korrigieren oder zurückrollen.

## Fehlerberichte aus der Anwendung

### Fehlerreferenz und Nutzerhinweise

Bei einem internen Serverfehler zeigt Pfarrplaner eine eigene Fehlerseite mit
der großen Pfarrplaner-Marke und einer Fehlerreferenz an. Diese Referenz sollte
bei einer Supportmeldung mitgeteilt werden. Wenn Sentry konfiguriert ist, ist
sie die Event-ID des Sentry-Eintrags und kann dort direkt zum betroffenen Issue
aufgelöst werden.

Die Fehlerseite bietet außerdem ein freiwilliges Formular für eine kurze
Beschreibung des Problems. Die Eingabe wird serverseitig an Sentrys
User-Feedback-Schnittstelle übermittelt und der Fehlerreferenz zugeordnet. Der
Sentry-API-Token wird nicht an den Browser übertragen.

Für die Zuordnung werden zusätzlich zur DSN diese Produktionsvariablen benötigt:

```dotenv
SENTRY_FEEDBACK_TOKEN=
SENTRY_ORGANIZATION=
SENTRY_PROJECT=
```

Der Token benötigt nur die Berechtigung zum Schreiben von Projekt-Feedback.
Wenn diese Variablen fehlen oder Sentry nicht erreichbar ist, bleibt die
Fehlerreferenz sichtbar; die Anwendung zeigt dann an, dass der Hinweis nicht
automatisch übermittelt werden konnte.

Die Testseite `/__test/500` ist ausschließlich in den Umgebungen `local` und
`testing` verfügbar. Sie kann verwendet werden, um Fehlerseite und Feedback-
Formular nach einem Update zu prüfen.
