# Produktionsdeploy aus GitHub Actions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Release-Images nach erfolgreicher CI automatisch und seriell auf eine außerhalb des Repositories konfigurierte Produktionshost-Matrix ausrollen.

**Architecture:** `release.yml` baut das versionierte Image und das Manual. Ein nachgelagerter Job im geschützten `production`-Environment liest `vars.PRODUCTION_HOSTS` mit `fromJSON()`, richtet SSH mit Environment-Secrets ein und führt auf jedem Host Backup, Tag-Update und Statusprüfung aus.

**Tech Stack:** GitHub Actions, JSON-Variable, `webfactory/ssh-agent`, OpenSSH, vorhandene `./planer`-Produktionsbefehle.

**Spec:** `docs/superpowers/specs/2026-11-11-production-push-deploy-design.md`

## Global Constraints

- Keine realen Produktionshosts, privaten Schlüssel oder Backups im Repository.
- Produktionsupdates verwenden konkrete Release-Tags und benötigen `--backup-confirmed`.
- Der Rollout bleibt seriell und stoppt bei einem fehlgeschlagenen Host.
- Deutsche Commit-Beschreibung nach Conventional Commits.

## Review Focus

- Workflow-Dispatch darf keinen Deploy ohne Release-Tag auslösen.
- Eine leere oder ungültige Hostmatrix darf keinen stillen Null-Deploy erzeugen.
- SSH verwendet ausschließlich geprüfte Known Hosts.
- Shell-Sonderzeichen im konfigurierten Pfad dürfen den Remote-Befehl nicht verändern.
- Ein Hostfehler darf keinen parallelen Folge-Rollout starten.

### Task 1: Release-Workflow und Konfiguration dokumentieren

**Files:**
- Modify: `.github/workflows/release.yml`
- Modify: `docs/admin/installation-und-migration.md`
- Modify: `docs/manual/technikhandbuch/deployment.md`
- Test: `scripts/tests/release_process_test.sh`

- [ ] Fokussierte Workflow-Assertions ergänzen und deren erwartetes Scheitern prüfen.
- [ ] Nachgelagerten `deploy-production`-Job mit `needs`, `production`-Environment, JSON-Matrix, serieller Ausführung, SSH-Agent, Known Hosts und Remote-Update ergänzen.
- [ ] Einrichtung der Variable `PRODUCTION_HOSTS`, der Secrets und des Deploy-Key-Paars dokumentieren.
- [ ] Fokussierte Tests, `git diff --check` und relevante Dokumentationsprüfungen ausführen.
