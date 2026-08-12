[//]: # (TOC: 5. Updates)

# Updates

Pfarrplaner kann sich in einem Release an mehreren Stellen ändern: PHP-Abhängigkeiten, npm-Abhängigkeiten, Assets, Migrationen, Views, Queue-Verhalten oder Laufzeitprozesse. Ein gutes Update besteht deshalb nie nur aus einem `git pull`.

## Grundregel

Vor jedem Update brauchen Sie:

- aktuelles Backup der Datenbank
- Sicherung wichtiger Anwendungsdaten aus `storage/`
- kurze Prüfung offener lokaler Änderungen
- Klarheit, auf welchem Branch die produktive Instanz läuft

## Updatepfad 1: klassische Installation

Für klassische Installationen ist der vorgesehene Weg der Update-Befehl:

```bash
php artisan install:updates
```

Im Hintergrund wird das npm-Skript `install:updates` ausgeführt. Dieses Skript prüft unter anderem:

- ob ein Upstream-Branch vorhanden ist
- ob lokale Änderungen existieren
- ob `composer install` nötig ist
- ob `npm install` nötig ist
- ob Assets neu gebaut werden müssen
- ob Migrationen auszuführen sind
- ob Caches und Queue neu gestartet werden müssen
- ob Octane neu geladen werden muss, wenn die Instanz über `OCTANE_SERVER` in der `.env` mit Octane konfiguriert ist

### Trockentest

Vor produktiven Updates ist diese Prüfung sehr sinnvoll:

```bash
php artisan install:updates --dry-run
```

Damit sehen Sie, welche Schritte ein Update auslösen würde, ohne sie schon auszuführen.

### Typischer produktiver Ablauf

1. Backup erstellen.
2. `php artisan install:updates --dry-run`
3. Wartungsfenster oder kurzen Hinweis vorbereiten.
4. `php artisan install:updates`
5. Login, zentrale Ansichten, Mail und wichtige Berichte prüfen.

## Updatepfad 2: Docker Compose

Im Containerbetrieb hängt das genaue Update von Ihrer Build-Strategie ab. Der sichere Grundablauf bleibt aber gleich:

1. Backup von Datenbank und persistenten Volumes erstellen.
2. Neues Image bauen oder ziehen.
3. Container mit der neuen Version bereitstellen.
4. Migrationen im App-Container ausführen.
5. Queue-Worker und Scheduler auf die neue Version umstellen.
6. Funktionstest durchführen.

Typische Befehle sehen zum Beispiel so aus:

```bash
docker compose pull
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

Wenn Sie Assets bereits im Image bauen, entfällt der Asset-Build auf dem Zielsystem. Wenn nicht, muss auch im Containerkontext ein neuer Frontend-Build Teil des Updates sein.

## Rollback-Denken

Ein gutes Update ist erst dann sauber geplant, wenn Sie auch den Rückweg kennen.

Für klassische Installationen bedeutet das meist:

- vorherigen Git-Stand kennen
- Datenbank-Backup haben
- wissen, ob Migrationen rückwärts überhaupt sicher sind

Für Docker bedeutet das meist:

- vorheriges Image-Tag behalten
- alte Compose-Version oder Image-Referenz dokumentieren
- Datenbank-Backup vorhalten

## Nach jedem Update prüfen

- Anmeldung
- Startseite
- Kalender
- Öffnen und Speichern eines Gottesdiensts
- ein Bericht oder Export
- Passwort-Reset oder Testmail
- Queue, Scheduler und Logs
- Chromium-basierte Ausgaben
