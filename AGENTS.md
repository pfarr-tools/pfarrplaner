# Pfarrplaner — Repository Guide

Dieses Repository enthält die Docker-, Betriebs- und Migrationsschicht für
Pfarrplaner. Die Laravel-Anwendung liegt als Git-Subtree unter `src/`. Die
fachlichen und PHP-spezifischen Regeln von `src/AGENTS.md` gelten auch hier;
diese Datei ergänzt sie um die Regeln für das Root-Repository.

## Commit Messages

Commit-Nachrichten verwenden Conventional Commits. Die Beschreibung ist immer
auf Deutsch:

```text
<type>(<scope>): Kurze Beschreibung auf Deutsch.
```

Die Beschreibung ist ein großgeschriebener deutscher Satz. Der Punkt richtet
sich nach dem verwendeten Typ:

- `fix`: Beschreibung mit abschließendem Punkt
- `feat`: Beschreibung ohne abschließenden Punkt
- `chore`: Beschreibung ohne abschließenden Punkt
- `build`: Beschreibung ohne abschließenden Punkt
- `docs`: Beschreibung ohne abschließenden Punkt
- `test`: Beschreibung ohne abschließenden Punkt

Der Scope ist optional; für Releases wird `chore(release)` verwendet. Beispiele:

```text
feat: Docker-Betrieb für MariaDB ergänzen
fix: Fehler beim Wiederherstellen eines Backups beheben.
chore: Compose-Konfiguration aktualisieren
docs: Installationshandbuch ergänzen
chore(release): 2026.15.0
```

## Versionen und Releases

- Versionsnummern folgen dem bestehenden Pfarrplaner-Schema `YYYY.MAJOR.MINOR`.
- Die Anwendungsversion wird in `src/package.json`, `src/package-lock.json`
  und der `src/CHANGELOG.md` gemeinsam aktualisiert.
- Release-Commits heißen `chore(release): VERSION`.
- Releases werden aus einem geeigneten Arbeitsbranch in `main` übernommen.
- Nach einem Release wird `main` veröffentlicht; anschließend wird die Arbeit
  im Arbeitsbranch fortgesetzt.
- Ein Release darf erst nach relevanten Tests, Build-Prüfungen und einem Review
  der Migrations- und Betriebsdokumentation veröffentlicht werden.

## Git-Subtree

`src/` ist der Quellcode-Subtree des ursprünglichen Pfarrplaner-Repositories.
Änderungen am Anwendungscode müssen daher fachlich und technisch mit den
Regeln in `src/AGENTS.md` übereinstimmen. Beim Aktualisieren des Subtrees sind
keine Dateien aus `src/` stillschweigend zu überschreiben; lokale Änderungen
werden vorher geprüft und erhalten.

Root-Betriebsänderungen gehören in das Root-Repository. Änderungen an der
Laravel-Anwendung gehören unter `src/`. Eine Änderung, die beides betrifft,
wird in der Dokumentation und in den Commit-Nachrichten nachvollziehbar
beschrieben.

## Sicherheit und Daten

- Niemals `.env`, `.env.*`, Backups, Datenbank-Dumps, Objektbestände,
  `vendor/`, `node_modules/` oder Laufzeitdaten committen.
- Niemals `.env` oder eine Produktionsdatenbank mit Testdaten überschreiben.
- Vor Import, Restore, Pull und Migration müssen Zielumgebung und Datenbankname
  explizit geprüft werden; bei Unsicherheit ist der Vorgang abzubrechen.
- Legacy-Schlüssel wie `APP_KEY` und `DATABASE_KEY` dürfen nur über die
  dokumentierte Migrationslogik übernommen und niemals in Logs ausgegeben
  werden.
- Produktive Änderungen benötigen ein verifiziertes Backup und einen
  dokumentierten Rollback-Pfad.

## Docker und Betrieb

- `./planer` ist der zentrale Einstiegspunkt für Entwicklung, Datenpflege,
  Backups, Migrationen und Produktionsupdates.
- Portwerte kommen aus `.env`; keine festen lokalen Ports in Skripten oder
  Compose-Dateien einführen, wenn sie konfigurierbar sein müssen.
- MariaDB, Redis, MinIO, Soketi und Worker-Dienste müssen ihre gesundheits- bzw.
  betriebsrelevanten Abhängigkeiten berücksichtigen.
- MinIO ist der verbindliche Objektspeicher; neue Dateipfade dürfen nicht
  ungeprüft direkt auf `storage/app` festgelegt werden.
- Lokale Tests verwenden die isolierte Testdatenbank bzw. die vom
  `./planer test`-Befehl vorgegebene Isolation.

## Dokumentation

Jede Änderung an Installation, Updates, Migration, Backups, Storage,
Mail-Relay, Ports, Worker-Prozessen oder anderen Betreiber-Schnittstellen wird
im [Installations-, Migrations- und Betriebshandbuch](docs/admin/installation-und-migration.md)
nachgeführt.

Die drei Pfarrplaner-Handbücher bleiben inhaltlich konsistent:

- Benutzerhandbuch: ausschließlich nutzerbezogene Abläufe
- Administratorenhandbuch: Installation, Betrieb, Backups, Updates,
  Sicherheit und Fehlerbehebung
- Technisches Handbuch: Architektur, APIs, Integrationen und Erweiterungen

Deutsche Prosa verwendet korrekte Umlaute und `ß`; ASCII bleibt technischen
Bezeichnern, Dateinamen, URLs und CLI-Optionen vorbehalten.

## Arbeitsweise

- Vor Änderungen die betroffenen Dateien und den aktuellen Git-Status prüfen.
- Neue Funktionalität zuerst mit einem fokussierten Testfall beschreiben und
  anschließend implementieren.
- Vor einem Commit mindestens `git diff --check` sowie die für die Änderung
  relevanten Tests oder Builds ausführen.
- Vor Aussagen über Fertigstellung, funktionierende Tests oder einen
  erfolgreichen Push die entsprechende Prüfung frisch ausführen.
- Committen oder pushen nur auf ausdrücklichen Auftrag. Ein solcher Auftrag
  liegt für den aktuellen Repository-Aufbau ausdrücklich vor.
