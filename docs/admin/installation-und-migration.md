# Pfarrplaner: Installation, Migration und Betrieb

Dieses Dokument ist das verbindliche Installations- und Migrationshandbuch für
die Docker-Struktur von Pfarrplaner. Vor jeder Übernahme oder Aktualisierung
werden ein Backup, ein Testlauf und eine dokumentierte Rückfallmöglichkeit
benötigt.

## Zielstruktur und Erstinstallation

Die Betriebsdateien liegen am Repository-Root, der Laravel-Code unter
`src/`. Der Pflichtkern besteht aus PHP-FPM, Caddy, MariaDB, Redis, MinIO,
Horizon, Scheduler und Soketi. Dauerhafte Anwendungsdateien liegen im
MinIO-Bucket; lokale Volumes enthalten nur Laufzeitdaten, Logs und temporäre
Dateien. Die Entwicklungsumgebung verwendet `compose.yaml` mit PHP-FPM, die
Produktion `compose.production.yaml` mit Laravel Octane/Swoole.

Voraussetzungen sind Docker Engine mit Compose Plugin, mindestens 2 CPU-Kerne,
4 GB RAM, ausreichend Speicher für MariaDB, MinIO und Backups, ein DNS-Name
mit TLS sowie ein unabhängiges Backup-Ziel außerhalb des Servers.

```sh
./planer bootstrap
```

Für eine Produktionsumgebung wird der Produktionsmodus verwendet. Er fragt die
öffentliche URL oder den Hostnamen ab, setzt `APP_ENV=production` und legt eine
vollständig kommentierte `.env` mit allen verfügbaren Betriebs-, Backup-,
Mail-Relay- und Integrationsvariablen an:

```sh
./planer bootstrap
./planer up -d --build
./planer status
```

Der Produktionsbootstrap setzt das Compose-Projekt fest auf `pfarrplaner` und
fragt `APP_PROVIDER`, den ersten Administrator, Backup-Ziele und den Mailmodus
ab. Optional kann anschließend ein Restic-Backup oder eine Legacy-Installation
übernommen werden. Ohne Datenimport wird ein initialer
`Super-Administrator:in` angelegt. Entwicklung verwendet ausdrücklich
`./planer bootstrap --dev` und `./planer dev ...`.

Bei der Datenübernahme startet der Bootstrap die Produktionsdienste zunächst
selbst. Für ein Backup werden Ziel und Backup-ID abgefragt und der Restore nur
nach der Bestätigung `JA` angewendet. Für eine Legacy-Installation wird zuerst
ein Dry-Run ausgeführt; ein absoluter lokaler Pfad wird direkt gelesen, während
nur `user@host:/absoluter/pfad` SSH verwendet. Wird keine Quelle gewählt,
werden Migrationen, Rollen und der initiale Administrator eingerichtet.

`./planer bootstrap --dev --defaults` ist für automatisierte
Entwicklungsumgebungen geeignet; mit `--defaults` werden synthetische
Produktionsdefaults verwendet. Vor dem öffentlichen Betrieb müssen insbesondere `APP_URL`,
Backup-Ziel, Mail-Relay und `HORIZON_ALLOWED_EMAILS` geprüft werden. Die
kommentierte `.env.example` ist die vollständige Referenz aller Einstellungen.
Im Produktionsmodus setzt das Bootstrap `OCTANE_HTTPS=false`, weil Pfarrplaner
typischerweise hinter einem externen TLS-Reverse-Proxy betrieben wird. Der
Reverse Proxy terminiert TLS; die interne Verbindung zwischen Proxy, Caddy und
Octane bleibt HTTP. Wenn die konkrete Topologie eine direkte HTTPS-Verbindung
bis zu Octane benötigt, muss `OCTANE_HTTPS=true` ausdrücklich in `.env` gesetzt
werden. Für mehrere Instanzen auf einem Server fragt das
Bootstrap den Docker-Projektnamen ab; er muss pro Instanz eindeutig sein. Der
Default ist `pfarrplaner`.

Für die Weitergabe des extern beendeten HTTPS setzt das Bootstrap
`TRUST_PROXY=*`. Das ist für den Betrieb hinter einem Reverse Proxy erforderlich,
damit Laravel `X-Forwarded-Proto` und den HTTPS-Schemawechsel vertrauenswürdig
auswertet.

Der Horizon-Zugang wird über `HORIZON_ALLOWED_EMAILS` gesteuert. Die Variable
enthält eine komma-separierte Liste gültiger Benutzer-E-Mail-Adressen:

```env
HORIZON_ALLOWED_EMAILS=admin@example.org,office@example.org
```

Leerzeichen werden entfernt und die Prüfung ist unabhängig von Groß- und
Kleinschreibung. Eine leere Liste gewährt in Produktionsumgebungen niemandem
Zugriff auf `/horizon`.

`.env` wird nur angelegt, wenn sie fehlt, und niemals überschrieben. Das
Bootstrap erzeugt beim ersten Anlegen zufällige Werte für `APP_KEY`,
`DATABASE_KEY`, `TOKEN_SALT`, die MariaDB-/Redis-Passwörter, den MinIO-
Secret-Schlüssel und die Soketi-Schlüssel. Externe Zugangsdaten wie Mail-,
Stripe- oder andere Provider-Geheimnisse werden nicht automatisch gesetzt.
Backup-Ziele und Backup-Geheimnisse stehen ausschließlich in `backup.yaml`.
Produktion verlangt `APP_ENV=production`.

```sh
./planer dev up -d
./planer dev artisan migrate
./planer dev status
```

## Sichere Tests und Objektspeicher

```sh
./planer dev test
./planer artisan <befehl>
```

`./planer dev test` erzwingt SQLite im Speicher. `./planer dev artisan test` wird mit
einer Warnung blockiert und akzeptiert nur die exakte interaktive Eingabe `ja`.
`./planer dev fresh` benötigt zusätzlich `--force`.

`FILESYSTEM_DRIVER=s3` und `AWS_ENDPOINT` zeigen auf den Live-MinIO-Bucket.
Die Compose-Dateien beziehen den MinIO-Server als community-gepflegten,
öffentlichen Build von `ghcr.io/coollabsio`; die offiziellen MinIO-Community-
Images sind nicht mehr anonym aus Docker Hub oder Quay beziehbar. Ein eigener
Initialisierungsdienst bringt `mc` zusammen mit einer Shell mit, wartet auf den
S3-Endpunkt und legt den Bucket an. Erst danach starten App, Horizon und
Scheduler. Für produktive Umgebungen sollten die verwendeten Image-Tags nach
der Prüfung zusätzlich auf freigegebene Digests fixiert werden.
Backups verwenden eigene Restic-Repositories. Ein lokales Repository ist für
Entwicklung geeignet; Produktion muss ein unabhängiges SFTP-Repository
aktivieren.

Bei der Übernahme werden mindestens `storage/app`, `storage/inbox`, öffentliche
Upload-Verzeichnisse und alle datenbankreferenzierten Dateien geprüft.
Schlüssel werden nicht umbenannt. Cache, Sessions, Logs und temporäre
PDF-Dateien werden nicht als langlebige Objekte übernommen.

## Backups und Restore

`./planer backup create` startet einen kurzlebigen Backup-Container. Dieser
quiesziert App, Horizon und Scheduler, erstellt eine MariaDB-Sicherung und
exportiert den Live-MinIO-Bucket, legt beides mit Deployment-Konfiguration und
`manifest.json` in einem geschützten Staging-Verzeichnis ab und lädt denselben
unveränderlichen Satz in jedes aktivierte Restic-Repository. Restic verschlüsselt
und versioniert die Snapshots; AWS S3 ist kein Backup-Backend.

Der aktuelle MinIO-Export sichert Objektinhalt, Schlüssel und Größeninventar.
MinIO-Versionenhistorie, Tags und Bucket-Metadaten werden nicht als eigene
Historie reproduziert; falls die produktive Bucket-Konfiguration diese benötigt,
muss vor dem Restore ein entsprechendes MinIO-Wiederherstellungsverfahren
ergänzt werden.

Die Aufbewahrung wird separat ausgeführt: 14 tägliche, 8 wöchentliche und 12
monatliche Snapshots sind der Standard. `create` löscht niemals alte Backups.

```sh
./planer backup targets
./planer backup create
./planer backup list
./planer backup verify
./planer backup prune
./planer backup check
./planer backup init
./planer backup restore 20261012T030000Z --target offsite
./planer backup create
```

`./planer backup init` prüft vor der Initialisierung jedes aktivierten Ziels,
ob dort bereits ein Restic-Repository liegt. In diesem Fall wird das Ziel nur
nach der ausdrücklichen Bestätigung `j` oder `ja` (alternativ `y` oder `yes`)
geleert und neu initialisiert. Die Voreinstellung ist „Nein“; Enter oder eine
andere Eingabe bricht die Ersetzung ab. Vorhandene Backup-Daten werden dabei
nicht verändert. Das gilt auch für SFTP-Ziele.

Die interaktiven Ja/Nein-Fragen im Bootstrap zeigen den Standard in Groß- bzw.
Kleinschreibung: `[J/n]` bedeutet, dass Enter „Ja“ auswählt, `[j/N]` bedeutet,
dass Enter „Nein“ auswählt. Ungültige Eingaben werden erneut abgefragt.

`restore` schreibt standardmäßig in ein separates Verzeichnis. Ein Anwenden auf
eine laufende Installation ist erst mit `--apply --yes`, einer vorherigen
Sicherheitskopie und quieszierten Diensten zulässig. Die Wiederherstellung
benötigt nur ein einzelnes Offsite-Repository und niemals alte Spatie-Archive.
`backup.yaml`, Restic-Passwörter und SSH-Dateien müssen für einen Notfall
zusätzlich außerhalb des Systems verwahrt werden.
SSH-Schlüssel oder eine SFTP-Passwortdatei sowie `known_hosts` werden aus dem
projektbezogenen `secrets/`-Verzeichnis nach `/run/secrets/` eingehängt; sie
müssen auf dem Host dem Backup-Benutzer gehören und dürfen nicht gruppen- oder
weltweit lesbar sein. Bei Passwort-Anmeldung verwaltet der Backup-Dienst das
Passwort über einen temporären `SSH_ASKPASS`-Wrapper; es gehört weder in die
SFTP-URL noch in `backup.yaml`.

Für Cron reichen Docker Compose, eine POSIX-Shell und der Projektzugriff auf dem
Host; Restic, `mariadb-dump`, `mc`, SSH und der YAML-Parser befinden sich im
Backup-Image. Die CLI übernimmt die Sperre, daher darf Cron keine zweite
`flock`-Sperre verwenden.

## Aktualisierung mit kurzer Unterbrechung

```sh
./planer backup create
./planer update --backup-confirmed
```

Das Update verhindert parallele Ausrollungen, prüft `APP_ENV=production`, baut
das neue Image vor dem Umschalten, führt Migrationen mit diesem Image aus,
aktualisiert öffentliche Assets, ersetzt App/Worker/Scheduler und wartet auf
Gesundheitschecks. `--ref <tag|commit>` baut einen bestimmten Git-Stand.

Migrationen müssen expand-and-contract-kompatibel sein: erst neue Strukturen
hinzufügen, dann die Anwendung ausrollen, alte Strukturen erst später
entfernen. Nicht kompatible Änderungen erhalten eine geplante kurze
Wartungszeit. Das vorherige Image bleibt als Rollback-Grundlage erhalten.

### Release erstellen und ausrollen

Die Versionierung folgt dem bestehenden Pfarrplaner-Schema. Im sauberen
Arbeitsbaum wird der Release über den zentralen Einstiegspunkt erstellt:

```sh
./planer release
./planer release minor
git push origin main v2026.15.0
```

Der Release aktualisiert `src/package.json`, `src/package-lock.json` und
`src/CHANGELOG.md`, erstellt `chore(release): VERSION` und setzt den Tag
`vVERSION`. Die automatische Wahl ist ein Major-Release beim Jahreswechsel,
ein Minor-Release bei neuen Features und sonst ein Patch-Release.

Der frühere Einzel-Image-Build aus `src/scripts/docker-build.js` und der
zugehörige Tag-Build wurden entfernt. Das Produktionsimage wird ausschließlich
aus dem Root-Repository und dem veröffentlichten Tag gebaut:

```sh
./planer backup create
./planer update --ref v2026.15.0 --backup-confirmed
```

`update` baut zuerst das neue Image, führt danach die Migrationen in einem
separaten Container aus und aktualisiert anschließend Assets, Octane, Horizon
und Scheduler. Der bisher laufende App-Container bleibt bis zum erfolgreichen
Build und zur erfolgreichen Migration aktiv. Ein Tag-Update ist deshalb der
empfohlene Produktionsweg; ein Update vom lokalen, ungetaggten Arbeitsstand
bleibt für Notfälle möglich.

## Datenübertragung und Legacy-Migration

```sh
./planer data push user@ziel:/opt/pfarrplaner
./planer data pull user@quelle:/opt/pfarrplaner --source-down

./planer migrate legacy /var/www/pfarrplaner --prod --dry-run
./planer migrate legacy user@altserver:/var/www/pfarrplaner --dev --dry-run
./planer migrate legacy user@altserver:/var/www/pfarrplaner --prod --dry-run
./planer migrate legacy user@altserver:/var/www/pfarrplaner --prod --confirm
./planer migrate legacy user@altserver:/var/www/pfarrplaner --demo --confirm
```

Ein absoluter lokaler Pfad wird direkt gelesen; nur ein Pfad mit `user@host:`
verwendet SSH. Ohne Zieloption verwendet der Import weiterhin die Entwicklungsumgebung
(`--dev`). Für einen produktiven Import muss `--prod` ausdrücklich angegeben
werden; dabei wird `compose.production.yaml` verwendet und `APP_ENV=production`
in der Ziel-`.env` verlangt. `--demo` verwendet dieselbe Produktionsumgebung
und führt nach dem Import zusätzlich `php artisan demo:build` aus. Der
Demo-Import konfiguriert SMTP auf den internen Mailpit-Dienst (`mailpit:1025`),
sendet also keine Nachrichten an externe Empfänger. Die Mailpit-Oberfläche ist
unter dem lokal gebundenen `FORWARD_MAILPIT_PORT` erreichbar. Umgekehrt
verhindert der Import, dass `--dev` eine Zielumgebung mit `APP_ENV=production`
verwendet. `--dev`, `--prod` und `--demo` dürfen nicht gemeinsam angegeben
werden. Für `--demo` setzt der Import `DEMO_MODE=true`; dieser explizite
Schalter erlaubt dem DemoBuilder die produktionsgleiche Compose-Umgebung und
verhindert, dass ein fehlender Demo-Preflight als erfolgreicher Import endet.

`--database` und `--user` sind optionale Überschreibungen. Standardmäßig liest der
Legacy-Import `DB_DATABASE`, `DB_USERNAME`, `DB_HOST`, `DB_PORT` und
`DB_PASSWORD` aus der entfernten `.env`; als Fallback werden `MYSQL_DATABASE`,
`MYSQL_USER` und `MYSQL_PASSWORD` unterstützt. Die Datei wird als Daten geparst
und niemals als Shell-Code ausgeführt. Vor dem Import wird die echte
Datenbankverbindung getestet. Auf dem Legacy-Server muss dafür `mariadb-dump`
oder `mysqldump` installiert sein.

Befindet sich die alte Datenbank in Docker, erkennt das Skript automatisch den
ersten laufenden Container mit einem MariaDB-/MySQL-Image und führt den Dump
über `docker exec` und den MariaDB-Unix-Socket darin aus. Der in der
Legacy-`.env` konfigurierte Benutzer
und das dort konfigurierte Passwort bleiben maßgeblich; Container-Umgebungs-
variablen werden nicht automatisch als Ersatz verwendet. Ein Fehler `1045
Access denied` bedeutet daher, dass die `.env` nicht zu den tatsächlich
initialisierten Datenbankzugangsdaten passt oder der Benutzer keinen Zugriff
vom gewählten Container-Netzwerk hat.

Die kryptografische Identität wird ebenfalls aus der Legacy-`.env` gelesen:
`APP_KEY`, `DATABASE_KEY` und `DATABASE_CIPHER` sind erforderlich. Diese drei
Werte werden nach Erstellung des Legacy-Archivs in die lokale Ziel-`.env`
übernommen, weil sonst Laravel-Cookies und verschlüsselte Datenbankfelder nicht
lesbar wären. Die lokalen Zielwerte für `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`, `APP_URL` und MinIO bleiben unverändert. Vor der
Änderung wird eine Kopie unter `backups/legacy/target-env-<zeitstempel>.env`
angelegt und bei einem Fehler automatisch zurückgespielt.

`data push/pull` übertragen MariaDB per logischem Dump und den Live-MinIO-
Bucket; Backup-Buckets bleiben unberührt. Der Legacy-Import erstellt SQL-/
Dateiarchive mit SHA-256-Prüfsummen. Daten aus `failed_jobs` sowie den
Telescope-Tabellen werden beim Dump bewusst ausgelassen; die Tabellenstrukturen
bleiben erhalten. Anschließend importiert der Prozess MariaDB, führt Migrationen aus,
überträgt `storage/app`, `storage/inbox` und — falls vorhanden — den gesamten
Ordner `resources/bible` der Legacy-Installation in das lokale
`src/resources/bible`. Bereits vorhandene lokale Bibeldateien werden dabei
ergänzt oder überschrieben, aber nicht gelöscht. Die alte Installation bleibt
unverändert. Wenn der Ordner vorhanden ist, wird das Ziel-App-Image vor dem
Start neu gebaut, damit die Dateien auch in Produktionscontainern verfügbar
sind.

## Optionaler Maildienst

```sh
docker compose -f compose.production.yaml --profile mail up -d postfix
```

Postfix ist send-only und hat keinen veröffentlichten SMTP-Port. App und
Horizon erreichen den Dienst ausschließlich über das private Mail-Netzwerk auf
Port 587; die Anwendung stellt Mails über Horizon asynchron zu. Die vollständige
Konfiguration für direkte MX-Zustellung, Upstream-Relay, DKIM, Queue-Prüfung,
Volumes, Sicherheit und Rollback steht in [`docs/mail.md`](../mail.md).

## Fehlerbehebung und Rollback

Der Produktions-App-Container prüft den tatsächlich registrierten Endpunkt
`/api/health`. Der Endpunkt benötigt den in `APP_URL` konfigurierten Hostnamen,
weil die Produktionsanwendung Anfragen an fremde Hosts ablehnt. Ein direkter
Test auf dem Server muss deshalb den Host-Header mitsenden:

```sh
./planer exec app sh -lc \
  "curl -i -H 'Host: dev.example.org' http://127.0.0.1:8000/api/health"
```

Wenn der Container gesund ist, veröffentlicht der interne Caddy den in
`APP_PORT` konfigurierten lokalen Port. Ein fehlender `web`-Container oder ein
unhealthy `app`-Container verhindert die Port-Veröffentlichung.

```sh
./planer status
./planer logs app
./planer logs web
./planer logs mariadb
```

Bei Fehlern nicht blind `migrate:fresh` ausführen. Gesundheitsstatus und
Migration prüfen, gegebenenfalls das vorige Image starten und anschließend
das getestete Datenbank-/Objektbackup wiederherstellen.
