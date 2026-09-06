[//]: # (TOC: 5. Deployment und Betrieb)

# Deployment und Betrieb

## Klassisches Deployment

Der klassische Weg kombiniert:

- Git-Checkout
- Composer
- npm/Vite
- `.env`
- Migrationen
- Cache- und Prozesspflege

Für Erstinstallationen ist `php artisan pfarrplaner:install` der vorgesehene Weg. Für Updates dient `php artisan install:updates`.

## Docker-Deployment

Im Containerbetrieb sollte das Deployment reproduzierbar über Images und Compose-Dateien laufen. Entscheidend sind persistente Volumes, konsistente `.env`-Werte und ein sauberer Umgang mit Migrationen und Worker-Prozessen.

## Release-Kontext

Das Repository enthält Release-Skripte, die neben Versionsständen auch den Neubau und die Auslieferung des Handbuchs berücksichtigen.

## Health und Monitoring

Für Monitoring ist mindestens der Health-Endpunkt sinnvoll:

- `GET /api/health`

Zusätzlich sollten Sie Webserver, PHP-Prozesse, Queue, Scheduler und Datenbank überwachen.
