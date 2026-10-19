[//]: # (TOC: 5. Updates)

# Updates

Pfarrplaner kann sich in einem Release an mehreren Stellen ändern: PHP-Abhängigkeiten, npm-Abhängigkeiten, Assets, Migrationen, Views, Queue-Verhalten oder Laufzeitprozesse. Ein gutes Update besteht deshalb nie nur aus einem `git pull`.

## Grundregel

Vor jedem Update brauchen Sie:

- aktuelles Backup der MariaDB-Datenbank und der MinIO-Objekte
- Sicherung von `backup.yaml` und den benötigten Secrets außerhalb von Git
- kurze Prüfung offener lokaler Änderungen
- Klarheit, auf welchem Branch die produktive Instanz läuft

## Updatepfad 1: Docker Compose (vorgesehen)

Der Root-Befehl prüft die Produktionsumgebung, verlangt ein bestätigtes Backup,
baut das neue Image vor dem Umschalten, führt die Migration aus, aktualisiert
öffentliche Assets und startet App, Horizon und Scheduler neu:

```bash
./planer backup create
./planer update --ref v2026.15.0 --backup-confirmed
```

Ohne `--ref` wird der aktuelle Checkout verwendet. Für ein Release sollte ein
veröffentlichter Tag verwendet werden. Das Update verlangt eine produktive
`.env`, verhindert parallele Ausrollungen und wartet auf den Health-Check.

### Rollback

Das vorherige Image und das vorherige Release-Tag bleiben als Rückfallbasis
erhalten. Bei kompatiblen Migrationen wird zunächst auf das vorherige Image
zurückgestellt. Bei nicht kompatiblen Migrationen ist zusätzlich der
dokumentierte Datenbank- und MinIO-Restore aus dem Backup erforderlich.

### Typischer produktiver Ablauf

1. Backup erstellen.
2. Wartungsfenster oder kurzen Hinweis vorbereiten.
3. `./planer update --ref ... --backup-confirmed`
4. Login, zentrale Ansichten, Mail und wichtige Berichte prüfen.

## Manueller Compose-Ablauf für Sonderfälle

Der oben beschriebene `./planer update`-Weg ist maßgeblich. Ein manueller
Compose-Ablauf darf nur verwendet werden, wenn die Zielumgebung und die
Datenbank vorher ausdrücklich geprüft wurden:

1. Backup von Datenbank und persistenten Volumes erstellen.
2. Neues Image bauen oder ziehen.
3. Container mit der neuen Version bereitstellen.
4. Migrationen im App-Container ausführen.
5. Queue-Worker und Scheduler auf die neue Version umstellen.
6. Funktionstest durchführen.

Typische Befehle sehen zum Beispiel so aus:

```bash
docker compose -f compose.production.yaml build --pull app
docker compose -f compose.production.yaml run --rm --no-deps app php artisan migrate --force
docker compose -f compose.production.yaml up -d --wait web
```

Wenn Sie Assets bereits im Image bauen, entfällt der Asset-Build auf dem Zielsystem. Wenn nicht, muss auch im Containerkontext ein neuer Frontend-Build Teil des Updates sein.

## Rollback-Denken

Ein gutes Update ist erst dann sauber geplant, wenn Sie auch den Rückweg kennen.

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
