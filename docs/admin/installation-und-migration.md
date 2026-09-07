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
./planer bootstrap --prod
./planer prod up --build
./planer prod status
```

`./planer bootstrap --defaults` ist für automatisierte Entwicklungsumgebungen
geeignet; mit `--prod --defaults` werden synthetische Produktionsdefaults
verwendet. Vor dem öffentlichen Betrieb müssen insbesondere `APP_URL`,
Backup-Ziel, Mail-Relay und `HORIZON_ALLOWED_EMAILS` geprüft werden. Die
kommentierte `.env.example` ist die vollständige Referenz aller Einstellungen.
Im Produktionsmodus setzt das Bootstrap außerdem `OCTANE_HTTPS=true`, damit
Laravel hinter einem externen TLS-Reverse-Proxy sichere absolute Asset- und
Anwendungs-URLs erzeugt. Für mehrere Instanzen auf einem Server fragt das
Bootstrap den Docker-Projektnamen ab; er muss pro Instanz eindeutig sein. Der
Default ist `pfarrplaner`.

Der Horizon-Zugang wird über `HORIZON_ALLOWED_EMAILS` gesteuert. Die Variable
enthält eine komma-separierte Liste gültiger Benutzer-E-Mail-Adressen:

```env
HORIZON_ALLOWED_EMAILS=admin@example.org,office@example.org
```

Leerzeichen werden entfernt und die Prüfung ist unabhängig von Groß- und
Kleinschreibung. Eine leere Liste gewährt in Produktionsumgebungen niemandem
Zugriff auf `/horizon`.

`.env` wird nur angelegt, wenn sie fehlt, und niemals überschrieben. Setze
`APP_KEY`, `APP_ENV`, `APP_URL`, MariaDB-/Redis-Passwörter, MinIO-Zugangsdaten,
`BACKUP_ARCHIVE_PASSWORD` und ein unabhängiges `BACKUP_S3_ENDPOINT`. Produktion
verlangt `APP_ENV=production`.

```sh
./planer up
./planer artisan migrate
./planer status
```

## Sichere Tests und Objektspeicher

```sh
./planer test
./planer artisan <befehl>
```

`./planer test` erzwingt SQLite im Speicher. `./planer artisan test` wird mit
einer Warnung blockiert und akzeptiert nur die exakte interaktive Eingabe `ja`.
`./planer fresh` benötigt zusätzlich `--force`.

`FILESYSTEM_DRIVER=s3` und `AWS_ENDPOINT` zeigen auf MinIO oder S3. Der
Live-Bucket (`AWS_BUCKET`) und der Backup-Bucket (`BACKUP_S3_BUCKET`) müssen
getrennt sein. Ein Backup-Ziel auf derselben physischen Platte wie die einzige
MariaDB-/MinIO-Instanz ist kein Disaster-Recovery-Backup.

Bei der Übernahme werden mindestens `storage/app`, `storage/inbox`, öffentliche
Upload-Verzeichnisse und alle datenbankreferenzierten Dateien geprüft.
Schlüssel werden nicht umbenannt. Cache, Sessions, Logs und temporäre
PDF-Dateien werden nicht als langlebige Objekte übernommen.

## Backups und Restore

Spatie Laravel Backup erzeugt ein verschlüsseltes MariaDB-Archiv. MinIO-Objekte
werden mit derselben Backup-ID in ein unabhängiges S3-/MinIO-Ziel gespiegelt.
`verify_backup` ist aktiviert; die Retention beträgt standardmäßig 7 Tage
alle Backups, 16 Tage tägliche, 8 Wochen wöchentliche, 4 Monate monatliche
und 2 Jahre jährliche Backups.

```sh
./planer backup create
./planer backup list
./planer backup verify
./planer backup restore ./backups/pfarrplaner-<zeitstempel>.zip --dry-run --confirm
./planer prod backup create
```

Ein Restore ist destruktiv und darf nur nach Dry-Run, aktueller Sicherung und
expliziter Bestätigung erfolgen. Vor dem Produktivbetrieb muss ein Restore in
einem isolierten Compose-Projekt geübt werden. Alte Legacy-Dateien werden erst
nach erfolgreicher Abnahme gelöscht.

## Aktualisierung mit kurzer Unterbrechung

```sh
./planer prod backup create
./planer prod update --backup-confirmed
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
./planer prod backup create
./planer prod update --ref v2026.15.0 --backup-confirmed
```

`prod update` baut zuerst das neue Image, führt danach die Migrationen in einem
separaten Container aus und aktualisiert anschließend Assets, Octane, Horizon
und Scheduler. Der bisher laufende App-Container bleibt bis zum erfolgreichen
Build und zur erfolgreichen Migration aktiv. Ein Tag-Update ist deshalb der
empfohlene Produktionsweg; ein Update vom lokalen, ungetaggten Arbeitsstand
bleibt für Notfälle möglich.

## Datenübertragung und Legacy-Migration

```sh
./planer data push user@ziel:/opt/pfarrplaner
./planer data pull user@quelle:/opt/pfarrplaner --source-down

./planer migrate legacy user@altserver:/var/www/pfarrplaner --dev --dry-run
./planer migrate legacy user@altserver:/var/www/pfarrplaner --prod --dry-run
./planer migrate legacy user@altserver:/var/www/pfarrplaner --prod --confirm
./planer migrate legacy user@altserver:/var/www/pfarrplaner --demo --confirm
```

Ohne Zieloption verwendet der Import weiterhin die Entwicklungsumgebung
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

Postfix ist send-only und hat keinen öffentlichen Port 25. SMTP-Relay wird
über `MAIL_RELAY_HOST`/`MAIL_RELAY_PORT` mit TLS und optionaler Authentifizierung
konfiguriert. Direkte MX-Zustellung benötigt zusätzlich PTR/rDNS, SPF, DKIM,
DMARC, stabile Hostnamen, TLS und Bounce-Überwachung. DNS, rDNS und Mail-
Reputation werden beim Provider eingerichtet; SMTP-Relay bleibt empfohlen.

## Fehlerbehebung und Rollback

Der Produktions-App-Container prüft den tatsächlich registrierten Endpunkt
`/api/health`. Der Endpunkt benötigt den in `APP_URL` konfigurierten Hostnamen,
weil die Produktionsanwendung Anfragen an fremde Hosts ablehnt. Ein direkter
Test auf dem Server muss deshalb den Host-Header mitsenden:

```sh
./planer prod exec app sh -lc \
  "curl -i -H 'Host: dev.example.org' http://127.0.0.1:8000/api/health"
```

Wenn der Container gesund ist, veröffentlicht der interne Caddy den in
`APP_PORT` konfigurierten lokalen Port. Ein fehlender `web`-Container oder ein
unhealthy `app`-Container verhindert die Port-Veröffentlichung.

```sh
./planer prod status
./planer prod logs app
./planer prod logs web
./planer prod logs mariadb
```

Bei Fehlern nicht blind `migrate:fresh` ausführen. Gesundheitsstatus und
Migration prüfen, gegebenenfalls das vorige Image starten und anschließend
das getestete Datenbank-/Objektbackup wiederherstellen.
