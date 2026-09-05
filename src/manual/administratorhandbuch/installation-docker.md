[//]: # (TOC: 4. Installation mit Docker Compose)

# Installation mit Docker Compose

Dieser Weg ist für Umgebungen gedacht, in denen Pfarrplaner zusammen mit seinen Laufzeiten in Containern betrieben werden soll. Die inhaltliche Tiefe dieses Kapitels kann später noch ausgebaut werden; die Grundstruktur und der empfohlene Ablauf stehen bereits fest.

## Zielbild

Eine Docker-Compose-Installation sollte mindestens diese Bestandteile sauber trennen:

- App-Container für PHP und die Laravel-Anwendung
- Datenbank-Container
- optional separater Webserver oder Reverse Proxy
- optional separater Queue-Worker
- optional separater Scheduler-Container oder Cron im App-Umfeld

## Was in Docker trotzdem gleich bleibt

Auch im Containerbetrieb brauchen Sie weiterhin:

- eine gültige öffentliche URL
- HTTPS vor dem System
- persistente Datenbankdaten
- persistente Anwendungsdaten in `storage/`
- Mail-Konfiguration
- Chromium-Laufzeit für Puppeteer und Browsershot

## Empfohlene Compose-Bausteine

- `app`: Pfarrplaner-Anwendung
- `db`: MySQL oder MariaDB
- `queue`: optional eigener Worker-Prozess
- `scheduler`: optional eigener Scheduler-Prozess
- `proxy`: optional Nginx, Traefik oder ein externer Reverse Proxy

## Persistente Volumes

Mindestens diese Daten dürfen bei Container-Neustarts nicht verloren gehen:

- Datenbankdaten
- `storage/`
- gegebenenfalls Uploads, Caches oder exportierte Dateien, sofern sie betriebsrelevant sind

## Build oder Image

Es gibt zwei übliche Wege:

1. Eigenes Image bauen, das Quellcode, Composer-Abhängigkeiten, Node-Build und Laufzeit bereits enthält.
2. Quellcode und Builds beim Start oder beim Deployment in den Container bringen.

Für produktive Umgebungen ist ein reproduzierbares Image meist die sauberere Wahl.

## CLI-Installer im Container

Auch bei Docker Compose wird die Anwendung mit dem neuen CLI-Installer fertig eingerichtet, zum Beispiel:

```bash
docker compose exec app php artisan pfarrplaner:install
```

Oder nicht-interaktiv mit denselben Optionen wie bei der klassischen Installation.

## Besondere Punkte bei Docker

- Dateirechte zwischen Host und Container müssen passen.
- Die öffentliche URL in `APP_URL` muss auf die echte Außenadresse zeigen, nicht auf einen internen Containernamen.
- Headless-Chromium braucht im Image die passenden Pakete und Bibliotheken.
- Queue- und Scheduler-Prozesse dürfen nicht davon abhängen, dass jemand interaktiv im Container angemeldet ist.

## Empfohlene Reihenfolge

1. Compose-Datei und Volumes anlegen.
2. Container für App und Datenbank starten.
3. Erreichbarkeit der Datenbank aus dem App-Container prüfen.
4. `php artisan pfarrplaner:install` im App-Container ausführen.
5. Assets, Mail, Login und Hintergrundprozesse testen.

## Noch gezielt zu ergänzen

Dieses Kapitel ist bewusst so aufgebaut, dass später zusätzliche Details leicht ergänzt werden können, zum Beispiel:

- konkrete Beispiel-`docker-compose.yml`
- Trennung von Build- und Runtime-Image
- Reverse-Proxy-Beispiele
- Health-Checks
- Backup von Volumes
- Rolling-Update-Strategien
