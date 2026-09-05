# Pfarrplaner Docker- und Migrationsarchitektur

## Ziel

Pfarrplaner erhält dieselbe sichere, Docker-basierte Betriebsstruktur wie Roo,
bleibt dabei bei MariaDB und verwendet MinIO als verpflichtenden S3-kompatiblen
Objektspeicher. Die Struktur unterstützt Entwicklung, Produktion, Backups,
SSH-Datensynchronisation und die Übernahme einer bestehenden Datei/MySQL-
Installation.

## Architektur

Das Repository erhält einen Root-Wrapper `planer`. Die Laravel-Anwendung bleibt
unter `src/`; Compose-Dateien, Dockerfiles, Betriebs- und Migrationsskripte
liegen am Repository-Root. Entwicklungs- und Produktionsdienste werden durch
separate Compose-Dateien und getrennte benannte Volumes isoliert.

Der Pflichtkern besteht aus App/PHP-FPM, Caddy, MariaDB, Redis, MinIO,
MinIO-Bucket-Initialisierung, Horizon und Scheduler. Soketi wird als eigener
Broadcasting-Dienst eingebunden. Mail wird über ein optionales Compose-Profil
mit einem send-only Postfix-Dienst aktiviert.

## Daten und Speicher

MariaDB wird per logischem Dump gesichert und übertragen; rohe Datenbankvolumes
werden nie zwischen Installationen kopiert. Dauerhafte Anwendungsdateien werden
auf MinIO/S3 gespeichert. Lokale Volumes bleiben auf Laufzeitdaten wie Cache,
Sessions, Logs und temporären PDF-/Dokumentdateien beschränkt.

`data push` und `data pull` übertragen die Live-MariaDB-Datenbank und alle
MinIO-Buckets. Das Verfahren versetzt Quelle und Ziel optional in den
Wartungsmodus, führt Zielmigrationen aus, ersetzt Daten vollständig, leert
Caches und stellt den vorherigen Betriebszustand auch bei Fehlern wieder her.

## Backups

Spatie Laravel Backup bleibt erhalten und erzeugt verschlüsselte MariaDB-
Archive einschließlich der ausdrücklich ausgewählten lokalen Laufzeitdateien.
Die bestehende Retention- und Benachrichtigungslogik wird übernommen und auf
Docker-Pfade angepasst. MinIO-Daten werden zusätzlich unter derselben Backup-ID
in ein unabhängiges Backup-Ziel gespiegelt. Live- und Backup-Bucket dürfen nie
identisch sein.

`planer backup create` koordiniert beide Teile, schreibt einen Manifest- und
Prüfsummenstatus und verifiziert Archive sowie Objekte. Restore ist eine
explizit bestätigte Operation mit Dry-Run und dokumentierter Wiederherstellung
in der Reihenfolge Objekte/Datenbank bzw. der für den konkreten Import nötigen
Reihenfolge.

## Sicherheit

`./planer test` erzwingt `APP_ENV=testing`, SQLite im Speicher und einen
isolierten Anwendungsschlüssel. `./planer artisan test` wird mit einer dicken
Warnung blockiert und verlangt die exakte interaktive Bestätigung `ja`.
Destruktive Befehle wie `migrate:fresh` werden ebenfalls geschützt.

Produktionsbefehle prüfen `APP_ENV=production`, verwenden ausschließlich die
Produktions-Compose-Datei und verlangen vor Updates ein bestätigtes aktuelles
Backup.

## Legacy-Migration

Die Migration akzeptiert einen SSH-Zugang zur vorhandenen Installation. Sie
führt zunächst einen lesenden Vorab-Check aus, erstellt einen unveränderlichen
Legacy-Dump und ein Archiv aller relevanten Datei-Roots, überträgt Dateien in
MinIO unter ihren bestehenden Schlüsseln, importiert die MariaDB-Datenbank,
führt Migrationen aus und prüft anschließend jede datenbankreferenzierte Datei
auf Existenz, Größe, MIME-Typ und Prüfsumme. Legacy-Dateien bleiben bis zum
erfolgreichen Abnahmetest erhalten.

## Optionaler Maildienst

Das `mail`-Profil aktiviert Postfix ausschließlich für ausgehenden Versand.
SMTP-Relay und direkte MX-Zustellung werden unterstützt. DKIM-Signierung wird
im Mail-Stack konfiguriert; SPF, DMARC, DNS und rDNS sind externe
Provider-/DNS-Voraussetzungen und werden vollständig im Installationshandbuch
beschrieben. Ohne Profil läuft kein Mailcontainer.

