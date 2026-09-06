# Pfarrplaner

Dieses Repository bündelt die Pfarrplaner-Anwendung mit ihrer Docker-basierten
Betriebs- und Migrationsstruktur. Der Laravel-Quellcode liegt unter `src/` und
wurde als Git-Subtree aus dem ursprünglichen Pfarrplaner-Repository importiert.
Das Originalprojekt verweist für diesen Betriebsweg auf dieses Repository.

Pfarrplaner ist eine kollaborative Plattform für evangelische
Kirchengemeinden. Die Anwendung unterstützt dabei, Gottesdienste gemeinsam zu
planen, Absprachen im Team zu organisieren, Urlaub und Abwesenheiten im Blick
zu behalten und Kasualien zu verwalten.

Das Projekt stammt aus dem [Evangelischen Kirchenbezirk
Balingen](https://www.kirchenbezirk-balingen.de) der [Evangelischen
Landeskirche in Württemberg](https://www.elk-wue.de) und wird praxisnah für
den Alltag in Gemeinden weiterentwickelt.

## Was Pfarrplaner bietet

- gemeinsame Planung von Gottesdiensten und anderen Terminen
- Übersichten für Teams, Dienste und Verantwortlichkeiten
- Verwaltung von Urlaub, Abwesenheiten und Vertretungen
- Bearbeitung von Kasualien und weiteren gemeindlichen Arbeitsabläufen
- webbasierte Zusammenarbeit für Pfarramt, Gemeindebüro und weitere Beteiligte

Die Anwendung basiert auf Laravel, Inertia.js und Vue.

## Architektur

Die Entwicklungsumgebung besteht aus:

- Laravel/PHP-FPM und Caddy
- MariaDB 11
- Redis mit Passwortschutz
- MinIO als verpflichtendem S3-kompatiblem Objektspeicher
- Soketi für WebSocket-Broadcasting
- Laravel Horizon und Scheduler
- Vite-Devserver
- Mailpit für lokale E-Mail-Tests

Die Produktionsumgebung verwendet Laravel Octane mit Swoole. Ein send-only
Postfix-Relay kann über das optionale Compose-Profil `mail` aktiviert werden.

## Voraussetzungen

Benötigt werden Docker Engine mit Docker Compose sowie Git. Für den lokalen
Entwicklungsbetrieb sollten die konfigurierten Ports frei sein; sie können in
`.env` geändert werden.

## Entwicklungsumgebung starten

```sh
./planer bootstrap
./planer up --build
./planer status
```

`bootstrap` führt interaktiv durch die Erstellung der lokalen `.env`. Die
Standardwerte vermeiden die üblichen Roo-Ports, können aber jederzeit geändert
werden. Die wichtigsten lokalen Endpunkte werden danach mit `./planer status`
angezeigt; typischerweise sind die Anwendung, Vite, MinIO, Soketi und Mailpit
über die in `.env` gesetzten Forwarding-Ports erreichbar.

Häufige Befehle:

```sh
./planer logs app
./planer shell
./planer artisan migrate
./planer composer install
./planer npm run build
./planer test
./planer down
```

Der sichere Testbefehl verwendet eine isolierte SQLite-Datenbank. Ein direkter
Aufruf von `./planer artisan test` wird wegen der möglichen Datenbankzerstörung
bewusst geschützt.

## Daten, Backups und Migration

```sh
./planer backup create
./planer backup verify
./planer data push user@server:/opt/pfarrplaner
./planer data pull user@server:/opt/pfarrplaner
```

Für eine bestehende Datei-/MySQL-Installation wird die Legacy-Migration genutzt:

```sh
./planer migrate legacy user@server:/var/www/pfarrplaner --dev --dry-run
./planer migrate legacy user@server:/var/www/pfarrplaner --prod --dry-run
./planer migrate legacy user@server:/var/www/pfarrplaner --prod --confirm
```

Ohne `--dev` oder `--prod` wird die Entwicklungsumgebung verwendet. Für einen
Produktionsimport muss `--prod` ausdrücklich angegeben werden; die Ziel-`.env`
muss dafür `APP_ENV=production` enthalten.

Dabei werden DB-Zugangsdaten und `APP_KEY`/`DATABASE_KEY` aus der Legacy-`.env`
gelesen. Die lokalen Infrastrukturwerte bleiben erhalten. Dateien werden nach
MinIO übernommen; Debugdaten wie `failed_jobs` und Telescope-Historie werden
nicht importiert. Der Dry-Run ist vor jeder bestätigten Migration erforderlich.

## Produktion

Produktionswerte werden ausschließlich in der produktiven `.env` hinterlegt.
Die Produktions-Compose-Datei verwendet persistente Volumes, interne
Backend-Netzwerke, Octane/Swoole und MariaDB. Ein Update erfolgt nach einem
verifizierten Backup:

```sh
./planer prod backup create
./planer prod update --backup-confirmed
```

Das optionale send-only-Mail-Relay wird mit den Relay- und TLS-Variablen aus
`.env` aktiviert:

```sh
docker compose -f compose.production.yaml --profile mail up -d postfix
```

## Releases und Updates

Ein Release wird aus einem sauberen Arbeitsbaum erstellt. Der bestehende
Pfarrplaner-Release-Mechanismus aktualisiert Version und Changelog in `src/`,
erstellt den semantischen Release-Commit und setzt den Tag:

```sh
./planer release
./planer release minor
git push origin main v2026.15.0
```

Es gibt keinen separaten Legacy-Docker-Image-Build mehr. Das Produktionsimage
wird beim kontrollierten Update aus dem veröffentlichten Tag gebaut:

```sh
./planer prod backup create
./planer prod update --ref v2026.15.0 --backup-confirmed
```

Der Build erfolgt vor dem Umschalten der laufenden Container. Migrationen
werden mit dem neuen Image ausgeführt; anschließend werden Octane, Horizon und
Scheduler neu gestartet und die Gesundheitschecks abgewartet.

## Dokumentation

Das verbindliche [Installations-, Migrations- und Betriebshandbuch](docs/admin/installation-und-migration.md)
beschreibt Konfiguration, Objektmigration, Backups/Restore, sichere Updates,
Mail-Relay, Daten-Push/Pull und Fehlerbehebung.

Die drei Handbücher und ihre gemeinsamen Medien liegen unter
[`docs/manual/`](docs/manual/). Eine Übersicht der Dokumentationsstruktur steht
in [`docs/README.md`](docs/README.md).

Die Anwendung selbst ist unter [`src/`](src/) dokumentiert. Die ursprüngliche
Projektbeschreibung befindet sich weiterhin in [`src/README.md`](src/README.md).
Das vollständige [Benutzerhandbuch](https://handbuch.pfarrplaner.de) beschreibt
die Bedienung der Anwendung.

## Bereitstellung und Unterstützung

Pfarrplaner ist freie Software für Kirchengemeinden. Gehostete Versionen
werden derzeit ausschließlich für Kirchengemeinden aus dem Kirchenbezirk
Balingen und dem Kirchenbezirk Herrenberg angeboten. Für Interesse an einer
Demoversion oder Fragen zur Nutzung wenden Sie sich bitte an
[Pfarrer Christoph Fischer](https://christoph-fischer.de).

Die Weiterentwicklung kostet Zeit und Geld. Wenn Ihnen Pfarrplaner hilft,
können Sie das Projekt freiwillig unterstützen:

- einmalig per [PayPal](https://paypal.me/potofcoffee)
- regelmäßig per [Liberapay](https://liberapay.com/christoph.fischer)

Ein besonderer Dank gilt dem [Laravel Framework](https://laravel.com) und den
Autorinnen und Autoren der eingesetzten Open-Source-Bibliotheken. Eine
Übersicht befindet sich in [`src/CREDITS.md`](src/CREDITS.md).

## Lizenz

Pfarrplaner ist freie Software unter der [GNU General Public License, Version 3](https://www.gnu.org/licenses/gpl-3.0.html)
oder einer späteren Version.
