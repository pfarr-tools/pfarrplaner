# Installationsverbesserungen für Issue #46 – Implementierungsplan

> **Für agentische Bearbeitung:** Die Schritte werden taskweise mit TDD und frischer Verifikation ausgeführt.

**Ziel:** `./planer install` soll neue Pfarrplaner-Instanzen auch aus ungetaggten Commits und mit einer bewusst weiterverwendeten `.env` sicher einrichten; `bootstrap` bleibt Alias.

**Architektur:** Der bestehende Shell-Bootstrap wird nicht dupliziert, sondern als Installationsimplementierung weiterverwendet. Ein Wrapper-Alias behandelt die Befehlsnamen, während der Installationszustand (`.env` vorhanden oder nicht), die Default-Werte und der selektive Austausch von Secrets in `scripts/bootstrap.sh` zentral entschieden werden. Der Build-/Imagepfad erhält eine lokale Commit-Referenz als gültige Produktionsquelle.

**Tech Stack:** Bash, Docker Compose, Shell-Fixture-Tests.

**Spec:** GitHub Issue #46: https://github.com/pfarr-tools/pfarrplaner/issues/46

## Globale Einschränkungen

- `.env`, Backups, Laufzeitdaten, `vendor/` und `node_modules/` dürfen nicht versioniert oder unbeabsichtigt überschrieben werden.
- Produktions- und Testdatenbanken bleiben vollständig getrennt.
- Bestehende `bootstrap`-Aufrufe bleiben funktionsfähig.
- Neue oder geänderte Betriebsabläufe werden im Installationshandbuch dokumentiert.
- Vor Abschluss laufen fokussierte Shell-Tests, `git diff --check` und relevante Format-/Dokumentationsprüfungen.

## Review-Fokus

- Vorhandene `.env` ohne Bestätigung: Datei und Werte bleiben unverändert; Test in `scripts/tests/bootstrap_test.sh`.
- Vorhandene `.env` mit Zustimmung: vorhandene Werte erscheinen als Defaults; Test mit TTY-Fixture oder testbarer Prompt-Schnittstelle.
- Secret-/Domain-Ersetzung: nur explizit bestätigte Schlüssel werden erneuert; Test mit gemischter Bestätigung.
- Commit ohne Release-Tag: Installations-/Produktionsimage wird nicht wegen fehlendem Tag abgewiesen; Test des Build-/Image-Ref-Pfads.
- Alias und Hilfetext: `install` und `bootstrap` routen identisch; Routing-Test.

### Task 1: Kanonischen Installationsbefehl ergänzen

**Dateien:**
- Modify: `planer`
- Modify: `scripts/bootstrap.sh`
- Test: `scripts/tests/planer_routing_test.sh`

- [x] Failing tests für `./planer install`, `./planer bootstrap` und die aktualisierte Hilfe ergänzen.
- [x] Test ausführen und den fehlenden `install`-Pfad bestätigen.
- [x] `install` und `bootstrap` auf denselben Implementierungspfad routen und die Benutzertexte aktualisieren.
- [x] Routing-Test und `git diff --check` ausführen.

### Task 2: Vorhandene `.env` sicher fortsetzen

**Dateien:**
- Modify: `scripts/bootstrap.sh`
- Modify: `scripts/bootstrap-ui.sh` falls eine wiederverwendbare Eingabeabstraktion erforderlich ist
- Test: `scripts/tests/bootstrap_test.sh`

- [x] Failing Tests für Ablehnung, Übernahme bestehender Defaults und Erhalt unbekannter Werte ergänzen.
- [x] Tests rot ausführen.
- [x] Bestehende Werte über eine zentrale Env-Lesehilfe als Defaults verwenden und bei Nichtbestätigung ohne Schreibzugriff abbrechen.
- [x] Tests grün ausführen.

### Task 3: Selektiven Austausch von Secrets und Domain-Schlüsseln

**Dateien:**
- Modify: `scripts/bootstrap.sh`
- Test: `scripts/tests/bootstrap_test.sh`

- [x] Failing Test für getrennte Bestätigung von Sicherheitsgeheimnissen und Domain-Schlüsseln ergänzen.
- [x] Test rot ausführen.
- [x] Schlüsselgruppen explizit abfragen; nur bestätigte Gruppen neu generieren, alle übrigen Werte unverändert lassen.
- [x] Bootstrap-Test vollständig ausführen.

### Task 4: Ungetaggte Produktions-Commits installieren

**Dateien:**
- Modify: `scripts/build-production.sh` und/oder `scripts/update-production.sh`
- Modify: `compose.production.yaml` nur falls die Image-Übergabe betroffen ist
- Test: `scripts/tests/production_image_test.sh` oder neuer fokussierter Shell-Test

- [x] Failing Test für einen Commit ohne exakten Release-Tag ergänzen.
- [x] Test rot ausführen und die Stelle der Tag-Annahme dokumentieren.
- [x] Lokale Commit-/Image-Referenz als gültige Installationsquelle behandeln, ohne Release-Tags zu fingieren.
- [x] Test grün ausführen und den tatsächlichen Compose-Imagewert prüfen.

### Task 5: Dokumentation und Gesamtverifikation

**Dateien:**
- Modify: `docs/admin/installation-und-migration.md`
- Modify: relevante Referenzen in `README.md` und Handbüchern, falls sie `bootstrap` als einzigen Installationsbefehl nennen

- [x] Installationsablauf, Alias, vorhandene `.env` und ungetaggte Commits dokumentieren.
- [x] Relevante Shell-Tests, `git diff --check` und `./planer dev docs:check` ausführen.
- [x] Abschlussstatus von Issue #46 erst nach Review und expliziter Freigabe aktualisieren.
