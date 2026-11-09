[//]: # (TOC: 6. Deployment und Betrieb)

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
./planer backup create
./planer update --ref v2026.15.0 --backup-confirmed
```

`release` aktualisiert lokal Version, Changelog, Commit und Tag und pusht den
Tag. GitHub Actions baut daraufhin das versionierte Image
`pfarrtools/pfarrplaner:vVERSION` und veröffentlicht das Handbuch. `update`
zieht dieses Image, führt Migrationen aus, aktualisiert die öffentlichen Assets
und startet App, Horizon und Scheduler mit Health-Check neu. Der alte Image-
Stand bleibt als Rollback-Grundlage erhalten. Wenn `MAIL_POSTFIX_ENABLED=true` gesetzt ist,
aktiviert der Updatepfad automatisch das Compose-Profil `mail` und startet
Postfix gemeinsam mit App, Horizon und Scheduler. Dadurch bleibt der interne
Hostname `postfix` auch nach einem Containerwechsel erreichbar.

## Docker-Deployment

Im Containerbetrieb läuft das Deployment reproduzierbar über das veröffentlichte
Root-Produktionsimage und `compose.production.yaml`. Entscheidend sind persistente
MariaDB-, Redis- und MinIO-Volumes, konsistente `.env`-Werte, ein geprüftes
Backup und ein sauberer Umgang mit Migrationen, Horizon und Scheduler.

Die Produktions-Compose-Datei trennt interne Backend- und Mail-Netzwerke von
einem nicht internen `sentry-egress`-Netzwerk. App, Horizon und Scheduler sind
an dieses Egress-Netzwerk angebunden, damit der Sentry-Client den in
`SENTRY_LARAVEL_DSN` konfigurierten Host über HTTPS erreichen kann. Der
Egress-Pfad muss in Firewall und DNS des Servers erlaubt sein; die internen
Netzwerke bleiben ohne direkten externen Zugriff.

## Release-Kontext

Das Repository enthält Release-Skripte, die neben Versionsständen auch den
Neubau und die Auslieferung des Handbuchs berücksichtigen. Manual-Operationen
können unabhängig davon ausgeführt werden:

```sh
./planer dev manual all
./planer dev manual deploy --dry-run
./planer dev manual deploy
```

## Health und Monitoring

Für Monitoring ist mindestens der Health-Endpunkt sinnvoll:

- `GET /api/health`

Zusätzlich sollten Sie Caddy, den App-Health-Endpunkt, Horizon, Scheduler,
MariaDB, Redis, MinIO und – bei aktiviertem Mail-Profil – Postfix überwachen.
