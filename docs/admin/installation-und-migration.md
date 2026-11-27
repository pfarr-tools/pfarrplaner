# Pfarrplaner: Installation, Migration und Betrieb

Dieses Dokument ist das verbindliche Installations- und Migrationshandbuch für
die Docker-Struktur von Pfarrplaner. Vor jeder Übernahme oder Aktualisierung
werden ein Backup, ein Testlauf und eine dokumentierte Rückfallmöglichkeit
benötigt.

## Schutz der Browser-Testzugänge

Dusk-Hilfsrouten (`/_dusk` und `/_dusk/*`) erlauben eine Test-Anmeldung ohne
Passwort. Sie sind ausschließlich bei gleichzeitigem `APP_ENV=dusk` und
`DUSK_ENABLED=true` freigegeben. In normalen Entwicklungs-, Test- und
Produktionsumgebungen blockiert die Anwendung diese Zugänge mit HTTP 404,
auch wenn `DUSK_ENABLED` versehentlich aktiviert wurde.

Der isolierte `dusk:run`-Testlauf setzt beide Werte standardmäßig. Eigene
Dusk-Konfigurationen müssen ebenfalls `APP_ENV=dusk` verwenden; niemals die
Produktionsumgebung dafür umstellen. Produktionsinstallationen sollen keine
Composer-Entwicklungsabhängigkeiten enthalten. Nach einem Update lässt sich
die Sperre ohne Anmeldung mit `GET /_dusk/user` prüfen: Erwartet wird HTTP 404.

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
./planer install
```

Für eine Produktionsumgebung wird der Produktionsmodus verwendet. Er fragt die
öffentliche URL oder den Hostnamen ab, setzt `APP_ENV=production` und legt eine
vollständig kommentierte `.env` mit allen verfügbaren Betriebs-, Backup-,
Mail-Relay- und Integrationsvariablen an:

```sh
./planer install
./planer up -d --build
./planer status
```

Die Installation setzt das Compose-Projekt fest auf `pfarrplaner` und
fragt `APP_PROVIDER`, den ersten Administrator, Backup-Ziele und den Mailmodus
ab. Zusätzlich fragt er die optionale Sentry-DSN (`SENTRY_LARAVEL_DSN`) und den
Sentry-Tag (`SENTRY_TAG`) für die Fehlerüberwachung ab. Optional kann
anschließend ein Restic-Backup oder eine Legacy-Installation
übernommen werden. Ohne Datenimport wird ein initialer
`Super-Administrator:in` angelegt. Entwicklung verwendet ausdrücklich
`./planer install --dev` und `./planer dev ...`. Der bisherige Befehl
`./planer bootstrap` bleibt als Alias erhalten.

Bei der Datenübernahme startet der Bootstrap die Produktionsdienste zunächst
selbst. Für ein Backup werden Ziel und Backup-ID abgefragt und der Restore nur
nach der Bestätigung `JA` angewendet. Für eine Legacy-Installation wird zuerst
ein Dry-Run ausgeführt; ein absoluter lokaler Pfad wird direkt gelesen, während
nur `user@host:/absoluter/pfad` SSH verwendet. Wird keine Quelle gewählt,
werden Migrationen, die Rollen über `roles:sync` und der initiale Administrator
eingerichtet.

Die versionierte Rollen- und Berechtigungsstruktur liegt in
`src/config/roles.php`. Nach Änderungen oder bei einer bestehenden Installation
kann der Abgleich zunächst ohne Datenbankänderung geprüft werden:

```sh
./planer artisan roles:sync --dry-run
./planer artisan roles:sync
```

Der Abgleich ist idempotent und löscht unbekannte bestehende Rollen und
Berechtigungen nicht automatisch.

`./planer install --dev --defaults` ist für automatisierte
Entwicklungsumgebungen geeignet; mit `--defaults` werden synthetische
Produktionsdefaults verwendet. Vor dem öffentlichen Betrieb müssen insbesondere
`APP_URL`, Backup-Ziel, Mail-Relay und `HORIZON_ALLOWED_EMAILS` geprüft werden.
Für YouTube-Livestreams ist zusätzlich die [Google-Integration](#google-und-youtube-integration)
einzurichten. Die kommentierte `.env.example` ist die vollständige Referenz
aller Einstellungen.

## Google und YouTube: Integration einrichten

Die Google-Integration wird für das Anlegen und Verwalten von YouTube-
Livestreams benötigt. Die OAuth-Anmeldedaten werden ausschließlich über `.env`
bezogen. Eine Datei `config/client_secret.json` wird nicht angelegt und darf
nicht in das Repository oder in ein Backup der Anwendung übernommen werden.

### 1. Google-Cloud-Projekt vorbereiten

1. Öffnen Sie die [Google Cloud Console](https://console.cloud.google.com/)
   und wählen Sie das Projekt aus, das für Pfarrplaner verwendet werden soll.
   Ein eigenes Projekt für die jeweilige Betreiberorganisation ist empfohlen.
2. Aktivieren Sie die **YouTube Data API v3**.
3. Richten Sie den OAuth-Zustimmungsbildschirm ein. Als Anwendungstyp genügt
   die interne Organisationsanwendung, sofern nur Konten der eigenen
   Organisation verwendet werden; andernfalls ist der von Google verlangte
   externe Veröffentlichungs- und Testprozess zu beachten.
4. Erstellen Sie unter **APIs und Dienste → Anmeldedaten** einen OAuth-
   Client vom Typ **Webanwendung**.
5. Tragen Sie als autorisierte Weiterleitungs-URI exakt folgende Adresse ein:

   ```text
   https://<öffentliche-app-url>/google/auth/city
   ```

   Dabei muss `<öffentliche-app-url>` ohne abschließenden Schrägstrich dem
   Wert von `APP_URL` entsprechen. Tragen Sie zusätzlich die öffentliche
   Basisadresse als autorisierten JavaScript-Ursprung ein:

   ```text
   https://<öffentliche-app-url>
   ```

   Für eine lokale Entwicklung mit der Standardadresse sind das
   `http://localhost:8180/google/auth/city` und
   `http://localhost:8180`.

### 2. Zugangsdaten in `.env` hinterlegen

Übernehmen Sie die Client-ID und das Client-Secret aus der Google Cloud
Console in die `.env` der passenden Umgebung:

```env
APP_URL=https://pfarrplaner.example.org
GOOGLE_APP_CLIENT_ID=<client-id>.apps.googleusercontent.com
GOOGLE_APP_SECRET=<client-secret>
```

`GOOGLE_APP_SECRET` ist ein Zugangsschlüssel und darf nicht in Issues, Logs,
Screenshots oder Versionskontrolle erscheinen. Die Werte werden nicht in
`config/` gespeichert. Nach einer Änderung müssen die Dienste mit der neuen
Umgebung neu erstellt werden:

```sh
./planer up -d --force-recreate app horizon scheduler
```

### 3. YouTube-Konto mit einem Ort verbinden

Die OAuth-Verbindung wird pro Kirchengemeinde beziehungsweise Ort hergestellt
und speichert dort die von Google ausgegebenen Zugriffs- und Erneuerungs-Token.
Melden Sie sich als berechtigte Administratorin oder berechtigter Administrator
an und öffnen Sie die OAuth-Route für den gewünschten Ort:

```text
https://<öffentliche-app-url>/google/auth/city?city=<ORT-ID>&nextStep=/
```

`<ORT-ID>` ist die numerische ID des Ortes. Die Anwendung leitet anschließend
zu Google weiter. Melden Sie sich dort mit dem YouTube-Konto an, das den
betreffenden Kanal und die benötigten Livestream-Berechtigungen verwaltet, und
bestätigen Sie den Zugriff. Nach dem Rücksprung prüft die Anwendung den
OAuth-Callback und speichert die Token in der Ortsdatenbank. Schützen Sie die
Datenbank und `APP_KEY` entsprechend den allgemeinen Sicherheitsvorgaben.

Anschließend öffnen Sie im Ortseditor den Reiter **Streaming** und hinterlegen
die YouTube-Kanaladresse sowie – falls automatische Übertragungen verwendet
werden – den aktiven und passiven Streamschlüssel. Die Streamschlüssel werden
in YouTube Studio erzeugt und niemals in Git oder im Handbuch dokumentiert.

### 4. Einrichtung prüfen

Erstellen Sie testweise einen Gottesdienst mit dem Reiter **Streaming** und
legen Sie einen YouTube-Livestream an. Prüfen Sie außerdem die geplanten
Aufgaben:

```sh
./planer artisan schedule:list
./planer artisan youtube:update
./planer artisan youtube:unpublish
```

Ohne `GOOGLE_APP_CLIENT_ID` oder `GOOGLE_APP_SECRET` werden die YouTube-
Aufgaben kontrolliert übersprungen. Ein Fehler `client_secret.json does not
exist` weist auf ein veraltetes Image oder einen nicht aktualisierten Dienst
hin; prüfen Sie dann die Variablen in `.env`, `APP_URL` und die
`--force-recreate`-Ausführung. Ein Fehler `redirect_uri_mismatch` bedeutet,
dass die URI in der Google Cloud Console nicht exakt mit
`APP_URL/google/auth/city` übereinstimmt.

Wenn `SENTRY_LARAVEL_DSN` gesetzt ist, benötigen App-, Horizon- und
Scheduler-Container ausgehenden HTTPS-Zugriff auf den konfigurierten
Sentry-Host (normalerweise TCP 443). Die Produktions-Compose-Datei bindet
diese Container dafür zusätzlich an ein nicht internes Egress-Netzwerk; die
fachlichen Backend- und Mail-Netzwerke bleiben intern. Nach einer Änderung an
der Compose-Konfiguration müssen die betroffenen Dienste neu erstellt werden:

```sh
./planer up -d --force-recreate app horizon scheduler
```

Ein fehlender Egress-Pfad verhindert das Senden von Sentry-Ereignissen auch
dann, wenn DSN und Sentry-Tag korrekt in `.env` gesetzt sind.

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

`.env` wird nur angelegt, wenn sie fehlt, und niemals ohne interaktive
Bestätigung verändert. Bei einer vorhandenen `.env` ist die Standardantwort auf
die Fortsetzungsfrage Nein. Nach der Bestätigung werden vorhandene Werte als
Eingabe-Defaults verwendet; unbekannte Werte bleiben erhalten. Domain-Werte und
Sicherheitsgeheimnisse werden zusätzlich jeweils separat zur Ersetzung bestätigt.
Die Installation erzeugt beim ersten Anlegen zufällige Werte für `APP_KEY`,
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
./planer dev test tests/Feature/ExampleTest.php
./planer dev test --filter=test_index_loads
./planer artisan <befehl>
```

Ein optionaler Datei- oder Verzeichnispfad begrenzt den PHPUnit-Lauf auf diese
Tests. `--filter` wird zusätzlich an PHPUnit weitergereicht und kann nach
Testname oder regulärem Ausdruck auswählen, zum Beispiel:
`./planer dev test tests/Feature/ExampleTest.php --filter=test_index_loads`.
`./planer dev test` erzwingt
SQLite im Speicher. `./planer dev artisan test` wird mit
einer Warnung blockiert; auch `./planer dev exec app php artisan test` wird ohne
Ausführung blockiert. Für beide Fälle ist `./planer dev test` zu verwenden.
`./planer dev fresh` benötigt zusätzlich `--force`.
Zusätzlich bricht die gemeinsame PHPUnit-Basis direkte Testaufrufe ab, wenn
`APP_ENV`, `DB_CONNECTION` oder die isolierte Testdatenbank nicht korrekt
gesetzt sind. Dadurch kann `RefreshDatabase` keine Dev- oder Produktionsdaten
mehr erreichen.

### Soketi- und Realtime-Prüfung

Die App verwendet intern `soketi:6001`; der Browser verwendet den in
`SOKETI_PORT` veröffentlichten Host-Port. App-ID, Key und Secret müssen in
Laravel und im Soketi-Container identisch sein. Produktionsupdates starten
Soketi vor App und Workern und warten auf den TCP-Gesundheitscheck. App und
Worker werden erst anschließend gestartet; sie verwenden den internen Namen
`soketi` im privaten Backend-Netzwerk. Die Prüfung darf keine
Produktionsdatenbank verwenden:

```sh
./planer dev status
./planer dev npm run test:soketi
```

Der Test verbindet einen Pusher-kompatiblen WebSocket-Client, veröffentlicht
ein signiertes Testereignis über die Soketi-HTTP-API und prüft den empfangenen
Payload. Bei Änderungen an `.env` sind App und Soketi neu zu erstellen:

```sh
./planer dev up -d --force-recreate app soketi vite
./planer dev npm run test:soketi
```

Keys und Secrets dürfen weder in Logs noch in Testausgaben erscheinen.

### Große Datei-Uploads

Der gemeinsame Uploader verarbeitet Bildbearbeitung und Variantenbildung im
Browser. Dateien unterhalb der Schwelle werden direkt als Multipart-Upload
gesendet; größere Dateien verwenden resumierbare Upload-Sessions. Die
Standardwerte sind 10 MiB Schwelle, 5 MiB Chunk-Größe und 60 Minuten
Session-Gültigkeit. Sie können über folgende Variablen angepasst werden:

```env
ATTACHMENT_UPLOAD_THRESHOLD=10485760
ATTACHMENT_UPLOAD_CHUNK_SIZE=5242880
ATTACHMENT_UPLOAD_EXPIRY_MINUTES=60
```

Temporäre Chunks liegen im konfigurierten Laravel-Storage und damit in der
Pfarrplaner-Installation im verbindlichen MinIO-Objektspeicher. Der Scheduler
bereinigt abgelaufene Upload-Sessions stündlich. Bei Änderungen an Storage-
oder Compose-Konfigurationen sind die üblichen Backup- und
`--force-recreate`-Prüfungen anzuwenden; temporäre Upload-Chunks dürfen nicht
als dauerhafte Anhangsdaten behandelt werden.

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

### FTP-Inbox für Scanner

Die Compose-Umgebungen enthalten den Dienst `ftp` als Eingang für Scanner und
andere Ablagequellen. Der Dienst schreibt in das persistente Volume
`ftp_inbox`; die App sieht denselben Inhalt unter
`/var/www/html/storage/inbox`. Die Laravel-Inbox-API übernimmt daraus nur PDF-,
JPG-, JPEG- und PNG-Dateien. Dauerhafte Anhänge werden anschließend wie bisher
im MinIO-Bucket gespeichert.

`./planer install` fragt den FTP-Benutzernamen, die Passivadresse, den
FTP-Port, den passiven Portbereich und – verdeckt – das FTP-Passwort ab. Der
Passivbereich muss aus dem Scanner-Netz erreichbar sein. Die Werte stehen in
`.env` und dürfen nicht in Tickets oder Git übernommen werden. Für
automatisierte lokale Einrichtungen erzeugt `./planer install --defaults` ein
zufälliges Passwort; für Produktion muss die Passivadresse anschließend auf
die tatsächlich erreichbare Serveradresse geprüft werden.

Die Inbox ist ein Eingang und kein Ersatz für MinIO. Noch nicht verarbeitete
Dateien werden nicht in den MinIO-/Restic-Satz aufgenommen; vor dem Löschen
des `ftp_inbox`-Volumes müssen sie verarbeitet oder separat gesichert werden.

## Lokale Docker-Artefakte nach Builds und Produktionsupdates

`scripts/build-production.sh` bereinigt nach jedem Produktionsbuild den
Docker-BuildKit-Cache und nicht mehr referenzierte Images. Zusätzlich bleiben
lokal nur das aktuelle Release-Tag des Pfarrplaner-Produktionsimages sowie die
aktuellen lokalen und veröffentlichten Image-Namen erhalten. Ältere Releases
bleiben im konfigurierten Docker-Registry-Repository verfügbar und werden nicht
aus der Registry gelöscht. Docker-Volumes, Container anderer Projekte und
Pfarrplaner-Laufzeitdaten werden von dieser Bereinigung nicht verändert.

`scripts/update-production.sh` führt dieselbe Bereinigung nach einem
erfolgreichen Healthcheck aus und behält dabei das gerade aktualisierte
Release-Tag. Scheitert das Update vor dem Healthcheck, bleiben die bisherigen
lokalen Release-Images als Rückfallpunkt erhalten. Scheitert die Bereinigung,
meldet der Update-Befehl einen Fehler; die bereits gestartete neue Version
bleibt aktiv und die Bereinigung kann mit demselben Skript erneut ausgeführt
werden.

Der GitHub-Actions-Produktionsworkflow aktualisiert den Root-Checkout des Hosts
vor Backup und Update mit `git pull --ff-only`. Dadurch verwendet das Update
den aktuellen Betriebs- und Bereinigungscode aus `main`; bei lokalen
Änderungen, die den Fast-Forward verhindern, bricht der Rollout vor dem Backup
und der Aktualisierung ab.

Die Bereinigung läuft auch nach einem fehlgeschlagenen Build oder Push. Der
ursprüngliche Fehlercode bleibt dabei erhalten; falls das neue Image nicht
entstanden ist, bleibt das zuletzt vorhandene Release-Tag als lokaler
Rückfallpunkt erhalten.

## Backups und Restore

`./planer backup create` startet einen kurzlebigen Backup-Container. Wenn die
App läuft, sperrt der Root-Befehl während der lokalen Erfassung den HTTP- und
API-Zugriff über den Laravel-Wartungsmodus. Horizon und Scheduler werden nur
dann angehalten, wenn sie vorher liefen; MariaDB und MinIO bleiben verfügbar.
Nach der lokalen Erfassung wird die App wieder freigegeben und die zuvor
laufenden Worker werden gestartet, während der Backup-Container den Restic-
Upload fortsetzt. Die Erfassung erstellt eine MariaDB-Sicherung und exportiert
den Live-MinIO-Bucket, legt beides mit Deployment-Konfiguration und
`manifest.json` in einem geschützten Staging-Verzeichnis ab und lädt denselben
unveränderlichen Satz in jedes aktivierte Restic-Repository. Restic verschlüsselt
und versioniert die Snapshots; AWS S3 ist kein Backup-Backend.

Ein bereits vor dem Backup aktivierter Laravel-Wartungsmodus bleibt bestehen.
Bei einem Fehler oder Abbruch stellt die Aufräumlogik den zuvor aktiven
Wartungs- und Dienstzustand soweit möglich wieder her. Wenn App, Horizon oder
Scheduler vorher nicht liefen, werden sie nicht ungefragt gestartet.
Der App-Healthcheck kann während dieser Wartungsphase erwartungsgemäß
fehlschlagen; der Container wird deshalb nicht heruntergefahren.

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

Das Update verhindert parallele Ausrollungen, prüft `APP_ENV=production`, holt
das veröffentlichte Produktionsimage vor dem Umschalten, startet für die
Migrationen die abhängigen Dienste und führt sie mit diesem Image aus,
aktualisiert öffentliche Assets, ersetzt App/Worker/Scheduler und wartet auf
Gesundheitschecks. `--ref <tag>` verwendet den passenden veröffentlichten
Image-Tag.
Ohne `--ref` holt `update` den aktuellen Branch zuvor mit
`git pull --ff-only`; lokale nicht-committete Änderungen oder nicht
automatisch vorwärts auflösbare Branches brechen das Update sicher ab.
`--build` erstellt stattdessen ausdrücklich ein lokales Produktionsimage; mit
`./planer build --ref <tag|commit>` kann dafür auch ein Commit gebaut werden.

Ist `MAIL_POSTFIX_ENABLED=true` gesetzt, aktiviert `./planer update` automatisch
das Compose-Profil `mail` und startet den internen Postfix-Dienst zusammen mit
App, Horizon und Scheduler. Dadurch bleibt `MAIL_HOST=postfix` nach dem Update
auflösbar. Bei einem externen SMTP-Server bleibt das Profil deaktiviert.

Migrationen müssen expand-and-contract-kompatibel sein: erst neue Strukturen
hinzufügen, dann die Anwendung ausrollen, alte Strukturen erst später
entfernen. Nicht kompatible Änderungen erhalten eine geplante kurze
Wartungszeit. Das vorherige Release bleibt in der Registry als Rollback-
Grundlage verfügbar und wird bei Bedarf erneut lokal gezogen.

### Poolbezogene Sichtbarkeit der Pfarrer:innen

Die Migration `2026_11_27_000000_create_pool_user_visibility_table` ergänzt die
Pivot-Tabelle `pool_user_visibility`. Sie enthält ausschließlich die
ausdrücklichen Sichtbarkeitszuweisungen von Benutzerkonten zu Pools. Bestehende
Poolmitgliedschaften in `pool_user` werden nicht verändert. Die Migration wird
wie alle Anwendungsmigrationen beim normalen Update ausgeführt:

```sh
./planer backup create
./planer backup verify
./planer update --backup-confirmed
```

Nach dem Update muss die Rolle bzw. Berechtigung mit dem üblichen Abgleich
geprüft werden:

```sh
./planer artisan roles:sync --dry-run
./planer artisan roles:sync
```

Die Rücknahme der Migration entfernt nur die ausdrücklichen
Sichtbarkeitszuweisungen; die normale Poolmitgliedschaft bleibt fachlich
unverändert. Vor einem produktiven Rollback sind trotzdem das vorherige Image
und ein verifiziertes Datenbankbackup gemeinsam zu verwenden, weil die ältere
Anwendung die neue Tabelle nicht kennt. Nach einer Rücknahme müssen die
Sichtbarkeitszuweisungen bei einem erneuten Vorwärtsupdate erneut geprüft und
gegebenenfalls wieder eingerichtet werden.

### API-Token sicher migrieren

Die Migration `2026_11_22_000001_hash_legacy_user_api_tokens` ersetzt bisherige
Klartextwerte in `users.api_token` durch SHA-256-Werte. Bereits gehashte Werte
bleiben unverändert. Der Authentifizierungsweg akzeptiert weiterhin den
ursprünglichen Tokenwert, sodass bestehende Integrationen und Kalenderlinks
beim Update keinen Tokenwechsel benötigen. Die Migration ist absichtlich
vorwärtsgerichtet: Ein Rollback der Anwendung allein stellt Klartextwerte nicht
wieder her. Für einen vollständigen Rollback sind das verifizierte Backup und
das vorherige Image gemeinsam wiederherzustellen.

`./planer update` führt die Datenmigration im normalen Ablauf aus. Vor einem
Produktionsupdate sind Datenbank und MinIO zu sichern und die Sicherung zu
verifizieren.

### Elektronische Opferunterschriften und revisionssichere Opferurkunden

Die Migration `2026_11_27_000002_add_electronic_offering_settlements` ergänzt
die stadtbezogene Option für elektronische Unterschriften sowie die Tabellen
`offering_settlements` und `offering_settlement_corrections`. Die Option wird
im Stadt-Editor aktiviert. Ist sie aktiv, muss die öffentliche Opfererfassung
für jede eingetragene Opferzähler:in eine Unterschrift als PNG übermitteln.

Nach erfolgreicher Übermittlung archiviert die Anwendung die signierten Bilder,
den normalisierten Abrechnungssnapshot, ein signiertes Manifest und die PDF-
Opferurkunde im konfigurierten privaten Standardspeicher (in Produktion
MinIO). Die Dateien werden nicht ersetzt; Integritätsprüfungen vergleichen
die gespeicherten SHA-256-Prüfsummen und die Manifest-Signatur.

Eine bereits finalisierte Abrechnung kann nur über den authentifizierten
Korrektur-Endpunkt mit Begründung geöffnet werden. Dabei wird die vorherige
Beleg-ID mit Benutzerkonto und Grund in `offering_settlement_corrections`
protokolliert. Die neue Eingabe erzeugt anschließend eine eigene Revision.

Vor dem produktiven Aktivieren sind wie bei jeder Schemaänderung ein
verifiziertes Backup und ein Rollback über das vorherige Image vorzubereiten:

```sh
./planer backup create
./planer backup verify
./planer update --backup-confirmed
```

Die bestehende öffentliche Opfererfassung bleibt unverändert, solange die
Option für die Stadt deaktiviert ist. Ohne aktivierte Option wird weiterhin
die bisherige Opferurkunde ohne elektronische Unterschrift erzeugt.

### Migration der Kontoklassifikation und des Sicherheits-Onboardings

Die Migration `2026_11_18_090000_persist_official_user_and_security_onboarding_state` ergänzt `users.is_official_user`, `users.security_onboarding_email_logins` und `users.security_onboarding_last_shown_at`. Bei Bestandskonten wird `is_official_user` auf `true` gesetzt, wenn zuvor ein nichtleerer Legacy-Passwort-Hash vorhanden war. Für alle Konten setzt die Migration `must_change_password` auf `false`. Die bisherigen Hashwerte bleiben in der Datenbank erhalten, werden nach dem Update aber von keinem Anmelde- oder Verwaltungsweg verwendet.

`./planer update` führt die Migration im normalen Updateablauf aus. Vor einem Produktionsupdate sind Datenbank und MinIO zu sichern und die Sicherung zu verifizieren:

```sh
./planer backup create
./planer backup verify
./planer update --backup-confirmed
```

Prüfen Sie nach dem Update die Anwendungs- und Datenbankgesundheit und testen Sie den E-Mail-Link-Login sowie die Passkey- und TOTP-Einrichtung mit einem dafür vorgesehenen Konto.

Die `down()`-Methode entfernt die drei neuen Spalten. Sie setzt `must_change_password` jedoch nicht auf frühere Einzelwerte zurück und rekonstruiert auch keinen vorherigen Anmeldestatus. Ein vollständiger Rückfall besteht deshalb aus dem vorherigen Anwendungsimage und der vor dem Update erstellten, verifizierten Datenbanksicherung. Führen Sie nicht lediglich `migrate:rollback` aus und starten dann die ältere Anwendung mit dem veränderten Schema. Verwenden Sie das dokumentierte Restore-Verfahren in einer quieszierten Umgebung; `.env`, Datenbank und MinIO dürfen dabei nicht mit Testdaten überschrieben werden.

### Migration der Zwei-Faktor-Spalten

Die Migration `add_two_factor_columns_to_users_table` ergänzt vier nullable
Spalten in der festen Tabelle `users`: das verschlüsselte TOTP-Geheimnis, die
gehashten Recovery-Codes, den Bestätigungszeitpunkt und den Replay-Marker. Die
Migration verwendet absichtlich nicht `TWO_FACTOR_TABLE`; diese Anwendung hat
genau eine authentifizierende Benutzertabelle. Zusätzliche Kontentabellen
benötigen eine eigene, geprüfte Migration.

Vor dem ersten produktiven Lauf sind Datenbank und MinIO zu sichern und die
Sicherung zu verifizieren:

```sh
./planer backup create
./planer backup verify
./planer update --backup-confirmed
```

Die Migration ist vorwärtsgerichtet. `migrate:rollback` verweigert deshalb die
automatische Entfernung der Spalten, damit aktive TOTP-Geheimnisse und
Recovery-Codes nicht versehentlich gelöscht werden. Für einen Rückfall wird
das vorherige Release-Image bei Bedarf aus der Registry gezogen und gestartet;
die verifizierte Datenbanksicherung wird mit dem separaten Restore-Verfahren
in einer quieszierten Umgebung wiederhergestellt;
die vollständige Reihenfolge steht im Abschnitt [Fehlerbehebung und
Rollback](#fehlerbehebung-und-rollback). Ein manuelles `DROP COLUMN` ist kein
zulässiger Rollback.

Die Migration `create_two_factor_login_challenges_table` ergänzt eine
historisch für gehashte und befristete TOTP-E-Mail-Codes verwendete Tabelle.
Der aktive TOTP-E-Mail-Codepfad wurde entfernt. Die Tabelle bleibt für bereits
ausgeführte Migrationen und die Bereinigung alter Datensätze bestehen; neue
Anmeldungen schreiben nicht mehr hinein. Beim Rollback werden ausschließlich
Login-Challenge-Datensätze entfernt, nicht Benutzerkonten oder TOTP-Material.
Die Migration `create_passwordless_login_links_table` speichert stattdessen
gehashte Einmal-Tokens für den passwortlosen E-Mail-Anmeldelink. Beide
Migrationen werden nach Updates wie jede Laravel-Migration über
`./planer update` ausgeführt.

### Migration der administrativen 2FA-Wiederherstellung

Die Migration
`2026_11_16_000001_add_two_factor_recovery_state_and_audit_table` ergänzt den
nullable Marker `users.two_factor_recovery_required_at` und die Tabelle
`two_factor_recovery_audits`. Das Markerfeld sperrt das Zielkonto nach einem
Faktorwiderruf bis zur bestätigten TOTP-Neueinrichtung. Die Audit-Tabelle hält
handelnde und betroffene Benutzer-ID, Supportfall-Referenz, IP-Adresse und
Zeitstempel; sie enthält keine Passwörter, TOTP-Geheimnisse oder
Recovery-Codes. Es gibt absichtlich keine kaskadierenden Fremdschlüssel, damit
Auditzeilen eine Kontolöschung überstehen. Die lokale Demo-Anonymisierung
löscht zugehörige Demo-Auditdaten ausdrücklich.

Die Migration
`2026_11_16_000002_add_two_factor_recovery_attempt_tracking_to_users` ergänzt
den Fehlversuchszähler `users.two_factor_recovery_attempts` und den Zeitstempel
`users.two_factor_recovery_attempted_at`. Beide Werte werden gemeinsam mit der TOTP-Codeprüfung unter einer
Datenbanksperre aktualisiert; damit ist für die Begrenzung kein geteilter Cache
erforderlich.

Beide Migrationen werden beim regulären `./planer update` ausgeführt. Sie sind
vorwärtsgerichtet und verweigern `migrate:rollback`, weil dadurch Recovery-
Marker, Fehlversuchszähler und Audit-Historie verloren gingen. Entfernen Sie
Spalten oder Tabelle nicht manuell. Erstellen und verifizieren Sie vor einem Update ein
Backup der Datenbank und des MinIO-Bestands. Ein erforderlicher Rollback muss
das vorherige Release-Image aus der Registry mit einer verifizierten
Datenbanksicherung im quieszierten Restore-Verfahren kombinieren.

Für die Wiederherstellungsaktion ist zwingend
`SESSION_DRIVER=database` erforderlich. Nur so kann Pfarrplaner aktive
Sitzungen benutzerbezogen widerrufen. Fehlversuche werden mit Zeitstempel am
Super-Admin-Konto gespeichert und unter derselben Datenbanksperre wie die
TOTP-Codeprüfung aktualisiert; dafür ist kein geteilter Cache erforderlich.
Ist `SESSION_CONNECTION` gesetzt, muss
der Verbindungsname derselbe wie `database.default` sein; eine leere Einstellung
verwendet die Standardverbindung. Die Docker-Beispielkonfiguration setzt den
Datenbanktreiber. Die separate `src/.env.example` für den eigenständigen
Laravel-Betrieb verwendet dagegen `file` und muss für diese Funktion angepasst
werden. Der Datei-Cache dieses Beispiels ist dafür unkritisch. Prüfen Sie stets
die wirksame Session-Konfiguration, nicht nur die Beispieldatei. Bei einem
anderen Session-Treiber oder einer abweichenden Session-Datenbank ist die
Recovery-Aktion deaktiviert und darf nicht manuell über Datenbankänderungen
umgangen werden.

### Release erstellen und ausrollen

Die Versionierung folgt dem bestehenden Pfarrplaner-Schema. Im sauberen
Arbeitsbaum wird der Release über den zentralen Einstiegspunkt erstellt:

```sh
./planer release
./planer release minor
```

Die automatische Wahl ist ein Major-Release beim Jahreswechsel,
ein Minor-Release bei neuen Features und sonst ein Patch-Release.

Der Release aktualisiert `src/package.json`, `src/package-lock.json` und
`src/CHANGELOG.md`, erstellt `chore(release): VERSION`, setzt den Tag
`vVERSION` und pusht Branch und Tag. Der Push startet den GitHub-Actions-
Workflow `Release`. Dieser baut und veröffentlicht das Produktionsimage als
`pfarrtools/pfarrplaner:vVERSION` und baut das vollständige Manual einschließlich
Dusk-Screenshot-Tests, statischer Website und PDFs. Anschließend wird die
Website über Rsync veröffentlicht.

```sh
./planer release
```

Für den Release-Workflow müssen im GitHub-Repository folgende Werte hinterlegt
sein:

- Secret `DOCKERHUB_USERNAME` und Secret `DOCKERHUB_TOKEN` für die
  Veröffentlichung des Produktionsimages
- Secret `MANUAL_DEPLOY_SSH_KEY` mit dem privaten Deployment-Schlüssel
- Secret `MANUAL_DEPLOY_KNOWN_HOSTS` mit dem geprüften Eintrag für den
  Manual-Host
- Variables `MANUAL_DEPLOY_USER`, `MANUAL_DEPLOY_HOST` und
  `MANUAL_DEPLOY_PATH` für das Rsync-Ziel

Für den optionalen automatischen Produktionsdeploy wird zusätzlich das GitHub
Environment `production` benötigt. Die Produktionsziele werden nicht im
Repository, sondern als Repository- oder Organisationsvariable
`PRODUCTION_HOSTS` gepflegt. Der Wert ist ein JSON-Objekt für die
GitHub-Actions-Matrix, zum Beispiel:

```json
{
  "include": [
    {
      "name": "prod-a",
      "user": "deploy",
      "host": "prod-a.example.org",
      "path": "/srv/pfarrplaner"
    },
    {
      "name": "prod-b",
      "user": "deploy",
      "host": "prod-b.example.org",
      "path": "/srv/pfarrplaner"
    }
  ]
}
```

Die konkreten Hostnamen und Pfade bleiben Betreiberkonfiguration. Im
Environment `production` werden außerdem folgende Secrets hinterlegt:

- `PRODUCTION_DEPLOY_SSH_KEY` mit dem privaten Ed25519-Schlüssel
- `PRODUCTION_DEPLOY_KNOWN_HOSTS` mit den zuvor geprüften Hostschlüsseln

Ein passendes Schlüsselpaar kann mit `ssh-keygen -t ed25519` erzeugt werden.
Der öffentliche Schlüssel wird auf jedem Zielhost für den Deploy-Benutzer in
`~/.ssh/authorized_keys` hinterlegt. Die Hostschlüssel sind vor dem Eintragen
unabhängig zu verifizieren; `ssh-keyscan` allein ist keine ausreichende
Authentifizierung des Zielhosts. Für das Environment `production` sollten
zusätzlich erforderliche Reviewer konfiguriert werden.

Der Job `deploy-production` läuft erst nach erfolgreichem Image- und
Manual-Job, arbeitet die Matrix seriell ab und beendet den weiteren Rollout
beim ersten Fehler. Jeder Host führt vor dem Update ein Backup aus. Das
Update verwendet den konkreten Release-Tag und niemals implizit `latest`.

Der Workflow läuft ausschließlich für Tags im Format `vYYYY.MAJOR.MINOR`.
Schlägt ein Manual- oder Image-Schritt fehl, bleibt der Release-Tag bestehen,
aber die Veröffentlichung muss nach Fehlerbehebung über den Workflow erneut
ausgeführt werden.

`update` zieht zuerst das neue Image, führt danach die Migrationen in einem
separaten Container aus und aktualisiert anschließend Assets, Octane, Horizon
und Scheduler. Ein Tag-Update ist deshalb der empfohlene Produktionsweg.
Für einen lokalen Notfall-Build stehen `./planer build --ref <tag|commit>` und
`./planer update --build` zur Verfügung; diese Wege sind nicht Teil des
regulären Release-Workflows.

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

`./planer dev data pull user@quelle:/opt/pfarrplaner` ersetzt die lokale
Entwicklungsdatenbank vollständig, importiert anschließend den Quelldump und
führt danach die Migrationen des lokalen Branches aus. Damit bleiben keine
lokalen Tabellen zurück, die in der Quelle noch nicht existieren. Der Vorgang
überträgt zusätzlich den Live-MinIO-Bucket; Backup-Buckets bleiben unberührt.
Die lokale `.env` wird nicht übernommen oder verändert. `APP_KEY` und
`DATABASE_KEY` müssen daher bereits zu den verschlüsselten Quelldaten passen.
Die Dev-Anwendung ist während des Imports im Wartungsmodus.

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
Bei `--prod` und `--demo` verwendet der Import automatisch das Produktionsimage
des exakten Release-Tags auf `HEAD`. Alternativ kann mit
`PFARRPLANER_IMAGE=pfarrtools/pfarrplaner:vVERSION` ein Image ausdrücklich
vorgegeben werden. Ein ungetaggter Produktionsstand wird für den Import als
lokales Image `pfarrplaner-app:commit-<kurzer-commit>` gebaut; ein veränderliches
Image `:latest` wird nicht automatisch verwendet.

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

Für den Verbindungs- und Exporttest verwendet der Legacy-Import zusätzlich
`--no-tablespaces`. Dadurch fordert `mysqldump` beziehungsweise `mariadb-dump`
keine Tablespace-Metadaten an, für die neuere MySQL-/MariaDB-Server das
weitreichende `PROCESS`-Recht verlangen können. Der Legacy-Datenbankbenutzer
benötigt damit weiterhin nur die für den logischen Datenbankdump erforderlichen
Rechte; die Option wird sowohl beim Dry-Run als auch beim späteren Export
verwendet.

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
bleiben erhalten. Dafür erstellt der Import zuerst einen vollständigen
`--no-data`-Dump und hängt anschließend einen reinen Datendump mit
`--no-create-info`, `--skip-triggers` und je einem `--ignore-table` für diese
Tabellen an. Diese zweistufige Strategie ist mit `mysqldump` und
`mariadb-dump` kompatibel; die MariaDB-spezifische Option
`--ignore-table-data` wird nicht vorausgesetzt. Anschließend importiert der
Prozess MariaDB, führt Migrationen aus,
überträgt `storage/app` und `storage/inbox` nach MinIO. Falls vorhanden, wird
der gesamte Ordner `resources/bible` der Legacy-Installation vor dem Start der
Zielanwendung in das persistente Docker-Volume `bible_resources` übernommen.
Laravel liest die Dateien weiterhin über `resource_path('bible')`; App, Horizon
und Scheduler verwenden dabei dasselbe Volume. Das Produktions-App-Image wird
nicht verändert und nicht neu gebaut. Bereits vorhandene lokale Bibeldateien
werden ergänzt oder überschrieben, aber nicht gelöscht. Die alte Installation
bleibt unverändert.

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

### Passkey-Registrierungsfehler diagnostizieren

Die Einrichtung meldet getrennt Fehler bei Vorbereitung, Gerät/Browser und Serverprüfung. Mit der angezeigten Diagnose-ID lassen sich die Meldungen `Passkey registration client failure` und `Passkey registration server failure` im Anwendungsprotokoll (`./planer logs app`) zuordnen. Protokolliert werden ausschließlich feste Fehlerkategorien und Browser-/Plattformfamilien, Phase, HTTP-Status und Korrelations-ID. Passkey-Requests werden von der Telescope-Aufzeichnung ausgeschlossen. Ohne gültige Sitzung oder Netzwerk kann die Browserdiagnose nicht ankommen.

Prüfen Sie bei Domainfehlern `APP_URL`, `PASSKEYS_RELYING_PARTY_ID` und `PASSKEYS_ALLOWED_ORIGINS` gegen die öffentliche HTTPS-Adresse, einschließlich Port und Reverse-Proxy-Konfiguration. Bei Gerätefehlern sind Bildschirmsperre, Browser und Passkey-Anbieter zu prüfen. Es gibt keine Einschränkung auf USB-/NFC-Schlüssel; auffindbare Passkeys mit PIN/Biometrie bleiben erforderlich. Die Anmeldung per E-Mail bleibt unverändert verfügbar. Weitere Schritte stehen im [Sicherheitskapitel](../manual/administratorhandbuch/sicherheit.md#fehler-bei-der-passkey-einrichtung-zuordnen).

Diese Diagnoseänderung benötigt keine Migration und keine neue Umgebungsvariable. Vor dem Produktiv-Update gelten weiterhin die Backup-Prüfungen dieses Handbuchs. Ein Rollback auf das zuvor verwendete Anwendungsimage benötigt keine Datenbankrücksetzung; vorhandene Passkeys bleiben erhalten.
