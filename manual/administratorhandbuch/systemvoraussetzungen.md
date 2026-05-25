[//]: # (TOC: 2. Systemvoraussetzungen)

# Systemvoraussetzungen

Pfarrplaner ist eine Laravel-Anwendung mit Vue-Frontend und zusätzlichen Werkzeugen für Dateiausgaben, Browser-Automatisierung und Dokumenterzeugung. Für einen stabilen Betrieb müssen deshalb mehrere Laufzeiten zusammenpassen.

## Mindestbestandteile einer produktiven Umgebung

- Linux-Server mit dauerhaftem Speicher
- Webserver wie Apache oder Nginx
- PHP **8.4**
- Composer
- Node.js mit npm für Build und manche Updates
- MySQL, MariaDB, PostgreSQL, SQL Server oder SQLite
- Cron
- Prozessüberwachung für Queue-Worker, falls Warteschlangen genutzt werden

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

## Datenbank

Der neue Installer unterstützt:

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

Node wird nicht nur bei der Erstinstallation gebraucht. Auch Updates können neue npm-Abhängigkeiten oder neue Assets mitbringen.

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

## Webserver

Der Webserver muss auf das Verzeichnis `public/` zeigen. Alles andere bleibt außerhalb des direkt erreichbaren Document Roots.

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

Ohne funktionierende Mailzustellung sind Passwort-Resets, Benachrichtigungen und manche Arbeitsabläufe unzuverlässig.

## Hintergrundaufgaben

Für einen vollständigen Betrieb sollten Sie mindestens einplanen:

- Cron für den Laravel-Scheduler
- Queue-Worker für verzögerte Aufgaben
- Neustart der Worker nach Deployments

Wenn Sie Octane oder RoadRunner verwenden, kommen eigene Betriebsaufgaben wie Reloads und Health-Checks dazu.
