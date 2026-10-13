[//]: # (TOC: 4. Installation mit Docker Compose)

# Installation mit Docker Compose

Dieser Weg ist der vorgesehene Produktionsweg für dieses Repository. Die
Betriebsdateien liegen am Root, die Laravel-Anwendung unter `src/`, und alle
wichtigen Operationen werden über `./planer` ausgeführt.

## Zielbild

Die Entwicklungsumgebung und die Produktionsumgebung haben unterschiedliche
Compose-Dateien. `compose.yaml` verwendet PHP-FPM und Vite; `compose.production.yaml`
verwendet Octane/Swoole und bindet nur den Webserver an den Host.

## Was in Docker trotzdem gleich bleibt

Auch im Containerbetrieb brauchen Sie weiterhin:

- eine gültige öffentliche URL
- HTTPS vor dem System
- persistente Datenbankdaten
- persistente MariaDB-Daten
- persistente Redis-Daten
- persistente MinIO-Objekte als verbindlichen Dateispeicher
- lokale Laufzeitdaten unter `src/storage/` und den konfigurierten Backup-Pfaden
- Mail-Konfiguration
- Chromium-Laufzeit für Puppeteer und Browsershot

## Compose-Dienste

Die Entwicklungsdatei enthält unter anderem:

- `app`: PHP-FPM und Laravel-Anwendung
- `web`: Caddy
- `vite`: Frontend-Entwicklungsserver
- `mariadb`, `redis`, `minio`: Infrastruktur
- `horizon` und `scheduler`: Hintergrundprozesse
- `soketi`: WebSocket-Broadcasting
- `mailpit`: lokale Mailprüfung

Die Produktionsdatei ergänzt beziehungsweise ersetzt dies durch:

- `app`: Laravel Octane/Swoole
- `web`: Caddy als Reverse Proxy
- `public-assets`: öffentlicher Asset-Snapshot
- `horizon` und `scheduler`
- `mariadb`, `redis`, `minio`, `soketi` und `backup`
- optional `postfix` im Compose-Profil `mail`

## Persistente Volumes

Mindestens diese Daten dürfen bei Container-Neustarts nicht verloren gehen:

- Datenbankdaten
- MariaDB-Volume
- Redis-Volume
- MinIO-Volume
- das Postfix-Queue-Volume `postfix_queue`, sofern das Mail-Profil verwendet wird
- `backup.yaml` und `secrets/` außerhalb von Git

Dateien werden nicht ungeprüft dauerhaft in `storage/app` abgelegt. Der
verbindliche Objektspeicher ist MinIO; siehe [Dateien und Verzeichnisse](../technikhandbuch/dateien-und-verzeichnisse.md).
Die Compose-Dateien beziehen den MinIO-Server als community-gepflegten
öffentlichen Build von `ghcr.io/coollabsio` und `mc` von `ghcr.io/golithus`,
weil die offiziellen MinIO-Community-Images nicht mehr anonym aus Docker Hub
oder Quay bezogen werden können. Für Produktionsumgebungen sind die geprüften
Image-Digests zusätzlich zu pinnen.

## Build oder Image

Es gibt zwei übliche Wege:

1. Eigenes Image bauen, das Quellcode, Composer-Abhängigkeiten, Node-Build und Laufzeit bereits enthält.
2. Quellcode und Builds beim Start oder beim Deployment in den Container bringen.

Für produktive Umgebungen ist ein reproduzierbares Image meist die sauberere Wahl.

## Erstinstallation

Die Root-Konfiguration wird interaktiv erzeugt:

```bash
./planer bootstrap --prod
```

Danach werden die Produktionsdienste gestartet:

```bash
./planer prod up --build
./planer prod status
```

Falls eine Laravel-Installation oder Migration im laufenden Container nötig ist,
wird sie mit `./planer prod exec app php artisan ...` ausgeführt. Die produktive
`.env` wird niemals durch Testdaten ersetzt.

## Besondere Punkte bei Docker

- Dateirechte zwischen Host und Container müssen passen.
- Die öffentliche URL in `APP_URL` muss auf die echte Außenadresse zeigen, nicht auf einen internen Containernamen.
- Headless-Chromium braucht im Image die passenden Pakete und Bibliotheken.
- Horizon und Scheduler laufen als eigene, überwachte Compose-Dienste.
- Für Produktionsmail wird der optionale send-only-Postfix mit `--profile mail` gestartet; er veröffentlicht keinen Host-Port.
- App und Horizon senden über `postfix:587`, während Mailpit ausschließlich in der Entwicklungsumgebung bleibt.

## Empfohlene Reihenfolge

1. `./planer bootstrap --prod` ausführen und `.env` prüfen.
2. `./planer prod up --build` starten.
3. `./planer prod status` und den Health-Endpunkt prüfen.
4. `./planer prod backup create` einrichten und einen Restore-Test planen.
5. Mail, Login, Dateiablage, Berichte und Hintergrundprozesse testen.
6. Für Mail siehe das [Mail-Betriebskapitel](mail.md), für Updates [Updates](updates.md).
