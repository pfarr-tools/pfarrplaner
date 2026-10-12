[//]: # (TOC: 5. Deployment und Betrieb)

# Deployment und Betrieb

## Repository- und Release-Struktur

Der Root-Checkout enthält die Betriebsdateien; `src/` ist der Laravel-Subtree.
Release- und Produktionsoperationen werden aus dem Root ausgeführt. Der
Laravel-Code darf nicht als unabhängiger Produktionscheckout neben dem
Root-Repository aktualisiert werden.

## Vorgesehener Produktionsweg

Ein Release und seine Auslieferung laufen über:

```sh
./planer release
git push origin main v2026.15.0
./planer prod backup create
./planer prod update --ref v2026.15.0 --backup-confirmed
```

`prod update` baut das Produktionsimage, führt Migrationen aus, aktualisiert
die öffentlichen Assets und startet App, Horizon und Scheduler mit Health-Check
neu. Der alte Image-Stand bleibt als Rollback-Grundlage erhalten.

## Klassische Installation (Legacy)

Der klassische Weg kombiniert:

- Git-Checkout
- Composer
- npm/Vite
- `.env`
- Migrationen
- Cache- und Prozesspflege

Für unabhängige klassische Anwendungs-Checkouts ist `php artisan pfarrplaner:install`
der vorgesehene Weg. Für Updates dient `php artisan install:updates`. Dieser
Pfad ist vom Root-Repository und seinem `./planer`-Betrieb getrennt.

## Docker-Deployment

Im Containerbetrieb läuft das Deployment reproduzierbar über das Root-
Produktionsimage und `compose.production.yaml`. Entscheidend sind persistente
MariaDB-, Redis- und MinIO-Volumes, konsistente `.env`-Werte, ein geprüftes
Backup und ein sauberer Umgang mit Migrationen, Horizon und Scheduler.

## Release-Kontext

Das Repository enthält Release-Skripte, die neben Versionsständen auch den
Neubau und die Auslieferung des Handbuchs berücksichtigen. Manual-Operationen
können unabhängig davon ausgeführt werden:

```sh
./planer manual all
./planer manual deploy --dry-run
./planer manual deploy
```

## Health und Monitoring

Für Monitoring ist mindestens der Health-Endpunkt sinnvoll:

- `GET /api/health`

Zusätzlich sollten Sie Caddy, den App-Health-Endpunkt, Horizon, Scheduler,
MariaDB, Redis, MinIO und – bei aktiviertem Mail-Profil – Postfix überwachen.
