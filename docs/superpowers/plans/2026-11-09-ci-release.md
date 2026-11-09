# CI- und Release-Automatisierung Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Pull-Request-Prüfungen und Release-Tag-Veröffentlichungen vollständig in GitHub Actions ausführen.

**Architecture:** Der bestehende lokale Releasepfad erzeugt nur Version, Commit und Tag. Ein PR-Workflow führt Qualitäts- und Testgates aus; ein Tag-Workflow baut und veröffentlicht Handbuch und Docker-Image mit GitHub-Secrets.

**Tech Stack:** GitHub Actions, Docker Compose, Docker Buildx, Docker Hub, SSH/Rsync, Bash, Node.js.

**Spec:** `docs/superpowers/specs/2026-11-09-ci-release-design.md`

## Global Constraints

- Tests laufen ausschließlich über die isolierte `./planer dev test`-Schnittstelle.
- `.env`, Zugangsdaten, Backups und Laufzeitdaten bleiben unverändert und unversioniert.
- Release- und Betriebsdokumentation bleibt in deutscher Prosa mit korrekten Umlauten.
- Keine Veröffentlichung oder Issue-Schließung durch automatisierte Tests allein.

## Review Focus

- Tag ohne `v`-Präfix: Workflow darf nicht versehentlich veröffentlichen; wird durch `v*`-Filter und Shell-Test geprüft.
- Fehlendes Secret: Workflow soll früh und verständlich scheitern; wird durch dokumentierte Secret-Liste und Workflow-Ausdrücke geprüft.
- PR-Tests mit isolierter Datenbank: Workflow muss `./planer dev test` nutzen; wird durch statische Workflow-Prüfung geprüft.
- Manual-Deployment: SSH-Agent und Known-Hosts müssen vor Rsync eingerichtet sein; wird durch statische Workflow-Prüfung geprüft.
- Docker-Veröffentlichung: Login muss vor dem Build/Push erfolgen und das Tag unverändert verwenden; wird durch statische Workflow-Prüfung geprüft.

### Task 1: Lokalen Releasepfad auf Versionierung und Tag begrenzen

**Files:**
- Modify: `src/scripts/release.js`
- Modify: `scripts/release.sh`
- Test: `scripts/tests/release_process_test.sh`

- [ ] Test zuerst aktualisieren: prüfe, dass Manual-Build/-Deployment und Produktionsimage-Push nicht mehr im lokalen Releasepfad liegen und dass Commit/Tag weiterhin erzeugt werden.
- [ ] Test ausführen und den erwarteten Fehlschlag beobachten.
- [ ] Manual-/Image-Veröffentlichung aus den Release-Skripten entfernen; vorhandene Versionierungs- und Taglogik erhalten.
- [ ] Betroffene Shell-Tests und `git diff --check` ausführen.

### Task 2: PR-CI um vollständige Tests ergänzen

**Files:**
- Modify: `.github/workflows/quality.yml`
- Test: `scripts/tests/release_process_test.sh` und YAML-/Textprüfungen

- [ ] Statische Prüfung für Trigger, Qualitätsgate, vollständige isolierte Tests und Cleanup ergänzen und zunächst rot ausführen.
- [ ] Workflow um einen separaten isolierten Testschritt erweitern, der `./planer dev test` verwendet.
- [ ] Statische Prüfung und relevante Shell-Tests grün ausführen.

### Task 3: Release-Tag-Workflow für Handbuch und Docker-Image ergänzen

**Files:**
- Create: `.github/workflows/release.yml`
- Modify: `scripts/tests/release_process_test.sh`
- Modify: `docs/admin/installation-und-migration.md`
- Modify: `docs/manual/technikhandbuch/deployment.md`

- [ ] Statische Workflow-Prüfung für `v*`-Tags, Docker-Hub-Login, SSH-Agent/Known-Hosts, Manual-Build/-Deploy, Image-Build/-Push und Cleanup rot ausführen.
- [ ] Tag-Workflow mit minimalen Permissions, Docker-Setup, Manual-Build, Rsync-Deployment und Image-Push implementieren.
- [ ] Secrets, erforderliche Repository-Einstellungen und den neuen Ablauf in beiden Betriebsdokumenten beschreiben.
- [ ] Statische Prüfungen, Shell-Tests und `git diff --check` grün ausführen.

### Task 4: Gesamtprüfung

**Files:**
- Verify: alle geänderten Dateien

- [ ] Alle `scripts/tests/*.sh` ausführen, soweit sie ohne externe Produktionszugänge laufen.
- [ ] YAML-/Formatprüfung und `git diff --check` ausführen.
- [ ] Diff gegen die Spezifikation und Issue-Anforderungen prüfen.
