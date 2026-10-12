[//]: # (TOC: 2. Systemvoraussetzungen)

# Systemvoraussetzungen

Pfarrplaner ist eine Laravel-Anwendung mit Vue-Frontend und zusätzlichen Werkzeugen für Dateiausgaben, Browser-Automatisierung und Dokumenterzeugung. Für einen stabilen Betrieb müssen deshalb mehrere Laufzeiten zusammenpassen.

## Mindestbestandteile des Docker-Betriebs

- Linux-Server mit dauerhaftem Speicher
- Docker Engine mit Docker-Compose-Plugin
- mindestens 2 CPU-Kerne und 4 GB RAM als Ausgangspunkt
- DNS-Name und TLS vor dem Caddy-Container
- ausreichend Speicher für MariaDB, Redis, MinIO und Backups
- unabhängiges Backup-Ziel außerhalb des Servers

Die Produktions-Compose-Datei bringt PHP, Caddy, MariaDB, Redis, MinIO,
Soketi, Horizon und den Scheduler als Container mit. Ein optionales
Postfix-Profil übernimmt die ausgehende Zustellung. Für den Betrieb wird
keine separat installierte PHP-, Node- oder Webserver-Laufzeit auf dem Host
benötigt.

Eine klassische Installation hat zusätzliche Anforderungen und wird separat in
[Klassische Installation](installation-klassisch.md) beschrieben.

## PHP-Anforderungen

Aus `composer.json` ergeben sich mindestens diese harten Anforderungen:

- PHP `^8.4`
- PHP-Erweiterung `ext-dom`
- PHP-Erweiterung `ext-zip`

Zusätzlich braucht Laravel in der Praxis weitere übliche Erweiterungen, zum Beispiel:

- `mbstring`
- `openssl`
- `pdo`
- zum gewählten Datenbanksystem passende PDO-Erweiterung
- `fileinfo`
- `tokenizer`
- `xml`
- `ctype`
- `json`

Wenn Sie Dateiausgaben, Importe oder Office-Dokumente nutzen, sollten Sie fehlende Basis-Erweiterungen vor der Installation gezielt prüfen, nicht erst bei der ersten Fehlermeldung.

## Datenbank und Objektspeicher

Im Docker-Betrieb ist MariaDB 11 der Datenbankdienst. MinIO ist der verbindliche
S3-kompatible Objektspeicher für Uploads und andere Anwendungsdateien.

Eine klassische Installation kann zusätzlich folgende Datenbanken verwenden:

- `mysql`
- `sqlite`
- `pgsql`
- `sqlsrv`

Für produktive Mehrbenutzer-Instanzen empfiehlt sich in der Regel MySQL oder MariaDB. SQLite ist vor allem für kleine Test- oder Demo-Umgebungen praktisch.

## Node.js, npm und Frontend-Build

Pfarrplaner nutzt Vite und ein modernes npm-Ökosystem. Für produktive Builds brauchen Sie:

- eine aktuelle Node.js-LTS-Version
- `npm`
- ausreichend Arbeitsspeicher für `npm install` und `npm run build`

Node wird im Docker-Build verwendet. Auf dem Host wird Node nur benötigt, wenn
Sie lokale Frontend- oder Manual-Builds außerhalb von Docker ausführen.

## Chromium, Puppeteer und Browsershot

Pfarrplaner verwendet unter anderem:

- `puppeteer`
- `spatie/browsershot`

Darum muss auf dem Zielsystem ein lauffähiger Chromium- beziehungsweise Chrome-Stack vorhanden sein. Prüfen Sie besonders:

- Chromium oder Google Chrome ist installiert.
- Alle nötigen Systembibliotheken für Headless-Chromium sind vorhanden.
- Der Benutzer der Webanwendung darf den Browser im Headless-Betrieb starten.
- Temporäre Verzeichnisse und Cache-Verzeichnisse sind schreibbar.

Fehlt diese Laufzeit, schlagen PDF-, Screenshot- oder Browser-basierte Ausgaben oft erst später im Betrieb fehl.

## Webserver und öffentliche Netze

Im Produktionsbetrieb nimmt Caddy die öffentliche Verbindung entgegen und reicht
sie intern an den App-Container weiter. Der Host-Port wird über `APP_PORT`
konfiguriert und standardmäßig nur an `127.0.0.1` gebunden; TLS kann davor über
einen externen Reverse Proxy terminiert werden.

Wichtig sind außerdem:

- HTTPS
- ausreichend Upload-Größen
- saubere Weiterleitung auf die öffentliche Basis-URL
- Schutz gegen direkten Zugriff auf interne Dateien außerhalb von `public/`

## Mail

Pfarrplaner verschickt Systemmails. Deshalb brauchen Sie:

- gültige Absenderadresse
- funktionierenden SMTP- oder anderen Mail-Transport
- SPF, DKIM und DMARC passend zur Absenderdomain
- im Docker-Produktionsbetrieb das optionale Compose-Profil `mail`
- ausgehender TCP-Port 25 oder ein erreichbarer authentifizierter SMTP-Relay

Ohne funktionierende Mailzustellung sind Passwort-Resets, Benachrichtigungen und manche Arbeitsabläufe unzuverlässig.

## Hintergrundaufgaben

Für einen vollständigen Docker-Betrieb sind `horizon` und `scheduler` eigene
Compose-Dienste. Sie werden beim Produktionsupdate gemeinsam mit `app`
aktualisiert. Ein Host-Cronjob für den Laravel-Scheduler ist in diesem Weg
nicht erforderlich.

Wenn Sie Octane oder RoadRunner verwenden, kommen eigene Betriebsaufgaben wie Reloads und Health-Checks dazu.
