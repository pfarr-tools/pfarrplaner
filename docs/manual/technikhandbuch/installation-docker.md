[//]: # (TOC: 2. Installation und Erstbetrieb)

# Installation und Erstbetrieb mit Docker

Dieses Kapitel beschreibt die vollständige Einrichtung einer Pfarrplaner-Instanz aus dem Root-Repository. Es richtet sich an technische Betreiberinnen und Betreiber. Der produktive Weg verwendet `compose.production.yaml`; `compose.yaml` ist für die lokale Entwicklung und die Manual-/Testwerkzeuge bestimmt.

## 1. Voraussetzungen

Benötigt werden:

- ein Linux-Server oder eine lokale Linux-/Docker-Umgebung,
- Docker Engine mit Docker Compose v2,
- Git für den Checkout,
- eine öffentliche DNS-Adresse und ein vorgeschalteter HTTPS-fähiger Webserver für Produktion,
- ausreichend Speicherplatz für MariaDB, Redis, MinIO-Objekte, Backups und Container-Images.

Der Server muss die verwendeten Container-Images aus den konfigurierten Registries beziehen können. Für produktive Images werden die freigegebenen Tags verwendet; Infrastruktur-Images sollten in der Betriebsdokumentation mit dem geprüften Stand festgehalten werden.

## 2. Repository und Konfiguration

Checkout und Befehle werden im Root des Repositories ausgeführt. Die Anwendung liegt unter `src/`, die Betriebslogik in `planer`, `compose*.yaml` und `scripts/`.

```bash
git clone https://github.com/pfarr-tools/pfarrplaner.git
cd pfarrplaner
```

Die `.env` bleibt lokal und wird niemals committed. Lassen Sie `./planer install` die Datei aus `.env.example` erzeugen; ein vorheriges manuelles Kopieren macht die Datei für den Bootstrap zu einer bestehenden Installation und kann Produktionsdefaults wie `APP_ENV=production` und `APP_DEBUG=false` verhindern. Vor dem Start müssen mindestens öffentliche URL, Datenbank, Redis, MinIO, Mail und – in Produktion – sichere Passwörter gesetzt sein. Portwerte werden über die `.env` angepasst; feste Ports gehören nicht in eigene Skripte.

Wichtige Werte sind:

- `APP_URL` für die öffentliche Adresse,
- `APP_KEY` und gegebenenfalls `DATABASE_KEY`,
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` und `DB_ROOT_PASSWORD`,
- `REDIS_PASSWORD`,
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` und `AWS_BUCKET`,
- `MAIL_*` für den gewählten Mailbetrieb,
- `COMPOSE_PROJECT_NAME` und bei Bedarf die `*_PORT`-Werte.

Produktiv müssen verpflichtende Variablen gesetzt sein; die Produktions-Compose-Datei bricht bei fehlenden Werten ab. Entwicklungsdefaults sind nur für lokale Entwicklung geeignet.

## 2.1 Befehlsübersicht

Der Root-Befehl wählt ohne Unterbefehl den Produktionskontext. Für Entwicklung wird der Kontext immer ausdrücklich mit `dev` angegeben:

| Bereich | Befehle |
| --- | --- |
| Produktion | `./planer up`, `down`, `restart`, `status`, `logs`, `exec`, `shell`, `artisan` |
| Entwicklung | `./planer dev up`, `down`, `restart`, `status`, `logs`, `exec`, `shell`, `artisan` |
| Entwicklung prüfen | `./planer dev test`, `composer`, `npm`, `pint`, `format`, `format:check`, `analyse`, `lint`, `docs:check`, `quality` |
| Handbücher | `./planer dev manual build`, `dusk`, `site`, `all`, `deploy`, `source-publish` |
| Produktion vorbereiten | `./planer install` bzw. `./planer bootstrap`, `./planer build`, `./planer release`, `./planer update`, `./planer migrate legacy` |
| Daten und Sicherungen | `./planer backup create`, `list`, `verify`, `restore`, `prune`, `check`, `init`; `./planer data` |

Unterbefehle und Optionen werden an `./planer` weitergereicht. Für die Manual-Befehle ist auch die Kurzform `./planer dev manual:site` beziehungsweise die entsprechende `manual:*`-Variante verfügbar. `./planer dev fresh --force` setzt die lokale Entwicklungsdatenbank vollständig zurück und darf nicht gegen eine produktive Umgebung verwendet werden.

## 3. Erstkonfiguration

Wählen Sie genau einen Betriebsweg pro Checkout und Compose-Projekt. Für Produktion erzeugt die interaktive Erstkonfiguration die Betriebswerte, fragt Provider, Administrator, Backup-Ziele und Mailmodus ab und kann optional einen bestehenden Datenbestand importieren:

```bash
./planer install --prod
```

`./planer bootstrap` bleibt dafür ein Alias. Vor einem Import werden Zielumgebung, Datenbankname und Backupquelle geprüft. Ein Produktiv- oder Legacy-Backup darf nicht in eine Testdatenbank importiert werden. Nach der interaktiven Initialisierung startet der Importpfad die benötigten Container, führt Migrationen und `roles:sync` aus und richtet den initialen Super-Administrator ein.

Für Entwicklung verwenden Sie einen separaten Checkout oder mindestens einen eigenen `COMPOSE_PROJECT_NAME` und eigene Ports/Volumes:

```bash
./planer install --dev
./planer dev up -d --build
./planer dev artisan migrate --force
./planer dev artisan roles:sync
```

Der Entwicklungs-Bootstrap legt bewusst keinen Administrator an. Für einen lokalen Administrator verwenden Sie danach den vorgesehenen Artisan-Befehl mit Passwortübergabe über stdin; das Passwort wird nicht als Argument protokolliert:

```bash
read -r -s -p 'Passwort: ' PFARRPLANER_ADMIN_PASSWORD; printf '\n'
printf '%s' "$PFARRPLANER_ADMIN_PASSWORD" | ./planer dev exec -T app php artisan pfarrplaner:initial-admin \
  --email=admin@localhost --first-name=Administrator --last-name=Pfarrplaner \
  --password-stdin --must-change-password
unset PFARRPLANER_ADMIN_PASSWORD
```

Wenn Entwicklungs- und Produktionsinstanz parallel laufen, müssen sie getrennte Checkouts oder mindestens unterschiedliche `COMPOSE_PROJECT_NAME`-Werte, Host-Ports und Docker-Volumes verwenden. Nie dieselbe `.env` oder Datenbank für beide Wege verwenden.

Bei der Einrichtung von Restic-Zielen prüft `./planer backup init`, ob bereits ein Repository vorhanden ist. Bestehende Backup-Daten werden nur nach der ausdrücklichen Bestätigung `j` oder `ja` ersetzt.

## 4. Images bauen und Dienste starten

Für lokale Entwicklung startet der zentrale Befehl die PHP-, Web-, Vite-, Infrastruktur- und Hintergrunddienste:

```bash
./planer dev up -d --build
./planer dev status
```

Die Entwicklungsumgebung enthält typischerweise `app`, `web`, `vite`, `manual`, `horizon`, `scheduler`, `mariadb`, `redis`, `minio`, `create-buckets`, `soketi` und `mailpit`. Der `backup`-Dienst wird nur mit dem Compose-Profil `tools` aktiviert.

Der produktive Start verwendet das veröffentlichte Image und die Produktions-Compose-Datei:

```bash
./planer up -d
./planer status
```

Bei einem lokalen Produktions- oder Vorabtest kann ein Image zunächst über `./planer build` beziehungsweise `./planer build --ref <tag|commit>` gebaut werden. Das erzeugte lokale Image muss anschließend mit `PFARRPLANER_IMAGE=pfarrplaner-app:<tag>` in der Produktions-`.env` ausgewählt werden; andernfalls verwendet Compose weiterhin das veröffentlichte Standardimage. App, Horizon und Scheduler warten auf die gesunden Infrastrukturabhängigkeiten. Die App wird erst nach einem erfolgreichen Health-Check dem Webserver zugeordnet.

## 5. Dienste und Datenhaltung

| Dienst | Aufgabe | Persistenz bzw. Schnittstelle |
| --- | --- | --- |
| `app` | Laravel-/PHP-Anwendung; in Produktion Octane/Swoole | `src/storage` in Produktion, Quellcode in Entwicklung |
| `web` | Caddy als HTTP-/Reverse-Proxy | Caddy-Daten und -Konfiguration |
| `vite` | Entwicklungsserver für Frontend-Assets | Host-Port aus `VITE_PORT` |
| `mariadb` | Anwendungsdatenbank | `mariadb_data` |
| `redis` | Cache, Sessions und Queue-Unterstützung | `redis_data` |
| `minio` | verbindlicher Objektspeicher für Anhänge und Dateien | `minio_data` |
| `horizon` | Queue-Worker | keine eigene Fachdatenpersistenz |
| `scheduler` | Laravel-Scheduler | keine eigene Fachdatenpersistenz |
| `soketi` | WebSocket-Broadcasting | keine Fachdatenpersistenz |
| `mailpit` | lokale Mailprüfung bzw. produktiver Diagnosezugang | in Produktion nur loopback-gebunden, keine Mailzustellung und keine Fachdatenpersistenz |
| `postfix` | optionales produktives Send-only-Mail-Relay | `postfix_queue` |
| `ftp` | FTP-Eingang für die Ablage-API | `ftp_inbox`, FTP-/Passivports aus `.env` |

MariaDB, Redis, MinIO und – bei aktiviertem Mailprofil – die Postfix-Warteschlange dürfen bei Container-Neustarts nicht verloren gehen. Anhänge werden nicht ungeprüft in `storage/app` abgelegt; MinIO ist der verbindliche Dateispeicher.

Der Dienst `ftp` verwendet die Variablen `FTP_USER`, `FTP_PASSWORD`,
`FTP_PASV_ADDRESS`, `FTP_PORT`, `FTP_PASV_MIN_PORT` und `FTP_PASV_MAX_PORT`.
Das Volume `ftp_inbox` wird beim FTP-Benutzer eingehängt und parallel als
`/var/www/html/storage/inbox` in den App-Containern eingebunden. Die
`FTP_INBOX_STORAGE_PATH`-Konfiguration lautet standardmäßig `inbox`. Der
Eingang bleibt bewusst vom MinIO-Storage und vom MinIO-/Restic-Backup getrennt.

## 6. Erstprüfung

Nach dem Start werden zuerst Containerstatus und Health-Endpunkt geprüft. Die `.env` wird nicht in die interaktive Shell exportiert; verwenden Sie daher die konkret konfigurierte URL:

```bash
./planer status
curl -fsS https://pfarrplaner.example.org/api/health
```

Für einen lokalen Entwicklungsstart mit dem Standardport lautet die Prüfung beispielsweise:

```bash
curl -fsS http://127.0.0.1:8180/api/health
```

## 6.1 Öffentliche HTTPS-Adresse

Die Produktions-Compose-Datei veröffentlicht Caddy absichtlich nur auf `127.0.0.1:${APP_PORT}`. DNS und TLS werden deshalb auf dem Host oder einem vorgeschalteten Reverse-Proxy eingerichtet. Bei einem Host-Caddy mit automatischer Zertifikatsverwaltung genügt beispielsweise:

```caddyfile
pfarrplaner.example.org {
    reverse_proxy 127.0.0.1:8080
}
```

Setzen Sie `APP_URL=https://pfarrplaner.example.org`, öffnen Sie in der Firewall nur 80/443 für den vorgeschalteten Proxy und lassen Sie den internen `APP_PORT` geschlossen. Der mitgelieferte produktive Caddy setzt für Laravel bereits `X-Forwarded-Proto=https`, `X-Forwarded-Host` und Port 443. Wenn ein anderer Proxy verwendet wird, müssen diese Forwarded-Header und die Vertrauenskonfiguration gleichwertig gesetzt werden.

Für den Bootstrap werden `openssl` und Docker benötigt. Bei SSH-basierten Backupzielen sind zusätzlich `ssh-keyscan` und `ssh-keygen` auf dem Host erforderlich; bei lokalen oder S3-/Restic-Zielen entfallen diese beiden Werkzeuge.

Danach sind mindestens folgende Funktionen zu prüfen:

1. Anmeldung mit dem beim Bootstrap angelegten Administratorkonto.
2. Öffnen der Startseite und einer Kalenderansicht.
3. Anlegen oder Öffnen eines Gottesdienstes.
4. Datei-Upload und Abruf aus MinIO.
5. Bericht oder PDF-Ausgabe mit Chromium.
6. Queue und Scheduler über Status und Logs.
7. Mailversand; in Entwicklung über Mailpit, in Produktion über das aktivierte Mailprofil.

Logs werden kontextbezogen gelesen:

```bash
./planer logs app
./planer logs horizon
./planer logs scheduler
```

## 7. Backups einrichten

Vor produktivem Betrieb werden `backup.yaml` und die geschützten Dateien unter `secrets/` außerhalb von Git eingerichtet. Ein vollständiger Satz enthält MariaDB, MinIO-Objekte, Restic-Konfiguration und bei Bedarf die Postfix-Warteschlange.

```bash
./planer backup init
./planer backup create
./planer backup verify
```

Backups benötigen einen Host-Timer oder eine andere verlässliche regelmäßige Ausführung. Ein Restore wird zunächst in ein separates Ziel geschrieben. Das Überschreiben einer produktiven Umgebung erfordert ausdrücklich `--apply --yes`, ein geprüftes Backup und einen dokumentierten Rückfallweg.

## 8. Sichere Befehlsgrenzen

- Entwicklung: `./planer dev ...`
- Produktion: `./planer ...`
- Tests ausschließlich mit `./planer dev test`; dieser setzt eine isolierte SQLite-In-Memory-Datenbank.
- `./planer dev exec app php artisan test` wird absichtlich blockiert, weil damit die normale Entwicklungsdatenbank gefährdet wäre.
- Migrationen und produktive Artisan-Befehle werden erst nach Ziel- und Backup-Prüfung ausgeführt.

Weitere Befehle und der Update-/Rollback-Ablauf stehen im [Kapitel zu Deployment und Betrieb](deployment.md). Das ergänzende [Administratorhandbuch zur Installation](https://handbuch.pfarrplaner.de/administratorhandbuch/installation-docker/) beschreibt den administrativen Ablauf.
