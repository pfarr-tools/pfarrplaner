# Technisches Handbuch und Policy-Dokumentation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Das Technikhandbuch als ausführliche Docker-Stack-Dokumentation neu ordnen und im Administratorhandbuch alle registrierten Policies mit ihren Verben dokumentieren.

**Architecture:** Die Änderung bleibt dokumentarisch. Bestehende technische API-, Architektur- und Qualitätskapitel bleiben als Referenzen bestehen; ein neuer Installations-/Stack-Schwerpunkt erklärt Compose und `./planer`. Eine statische Policy-Referenz bildet den aktuellen AuthServiceProvider und die Policy-Klassen ab.

**Tech Stack:** Markdown, Docker Compose, `./planer`, MkDocs/Manual-Build, bestehende Shell-Tests.

**Spec:** `docs/superpowers/specs/2026-11-09-technisches-handbuch-und-policy-dokumentation-design.md`

## Global Constraints

- Die Installation muss ausführlich dokumentiert sein.
- Root-Betrieb läuft über `./planer`; Entwicklungs- und Produktionskontext werden getrennt beschrieben.
- MinIO ist der verbindliche Objektspeicher.
- Tests dürfen keine Dev- oder Produktionsdatenbank verwenden.
- Deutsche Prosa verwendet Umlaute und `ß`.
- Es werden keine `.env`, Laufzeitdaten, Backups oder generierten Artefakte geändert oder hinzugefügt.

## Review Focus

- Installationsreihenfolge und Voraussetzungen: vollständiger Ablauf von Konfiguration bis Health-Check.
- Sicherheitsgrenze zwischen `./planer dev ...` und Produktion: keine gefährlichen Test-/Artisan-Aufrufe im falschen Kontext.
- Persistenz: MariaDB, Redis, MinIO und optionale Postfix-Warteschlange korrekt zugeordnet.
- Policy-Abdeckung: jede registrierte Policy und jede öffentliche Fähigkeit wird einmal erfasst.
- Manual-Navigation: neue Kapitel sind über Index und Stichwortverzeichnis erreichbar.

### Task 1: Docker-Stack und ausführliche Installation dokumentieren

**Files:**
- Modify: `docs/manual/technikhandbuch/index.md`
- Modify: `docs/manual/technikhandbuch/ueberblick.md`
- Modify: `docs/manual/technikhandbuch/architektur.md`
- Modify: `docs/manual/technikhandbuch/dateien-und-verzeichnisse.md`
- Modify: `docs/manual/technikhandbuch/laufzeit-und-build.md`
- Modify: `docs/manual/technikhandbuch/deployment.md`
- Create: `docs/manual/technikhandbuch/installation-docker.md`
- Modify: `docs/manual/technikhandbuch/stichwortverzeichnis.md`

- [ ] Bestehende Root- und Compose-Schnittstellen gegen `planer`, `compose.yaml`, `Dockerfile` und die produktive Compose-Datei abgleichen.
- [ ] Installationskapitel mit Voraussetzungen, Checkout/`.env`, Bootstrap, Image/Container-Start, Initialisierung, Health-Checks, Mail/Storage, Backup-Einrichtung und Erstprüfung schreiben.
- [ ] Technische Kapitel auf Dienste, Netzwerke, Volumes, Hintergrundprozesse und sichere Befehlskontexte ausrichten; API-/Qualitätsverweise erhalten.
- [ ] Navigation und Stichwortverzeichnis aktualisieren.
- [ ] Mit `git diff --check` und Manual-Dokumentationsprüfungen verifizieren.

### Task 2: Vollständige Policy-Referenz im Administratorhandbuch

**Files:**
- Create: `docs/manual/administratorhandbuch/berechtigungen.md`
- Modify: `docs/manual/administratorhandbuch/index.md`
- Modify: `docs/manual/administratorhandbuch/betrieb.md`
- Modify: `docs/manual/administratorhandbuch/stichwortverzeichnis.md`

- [ ] Registrierte Policy-Zuordnungen, direkt verwendete Sonder-Gates und öffentliche Policy-Methoden aus dem aktuellen Code erfassen.
- [ ] Für jeden Bereich eine Tabelle mit Objekt/Bereich, Verb und verständlicher Berechtigungsbeschreibung erstellen; Bedingungen wie Gemeinde-, Rollen- oder Objektbezug knapp benennen.
- [ ] Sonderberechtigungen wie Kalender, Benutzerverwaltung und fachliche `gd-*`-Berechtigungen getrennt von Modell-Policies erklären.
- [ ] Kapitel verlinken und Betriebskapitel auf die Referenz verweisen.
- [ ] Abdeckung gegen `AuthServiceProvider.php` und `src/app/Policies/*.php` prüfen.

### Task 3: Gemeinsame Qualitätsprüfung

**Files:**
- No planned production-code changes.

- [ ] `git diff --check` ausführen.
- [ ] `./planer dev format:check` ausführen und ausschließlich dokumentationsrelevante Verstöße beheben.
- [ ] `./planer dev docs:check` und die passenden Manual-CLI-Tests ausführen.
- [ ] Manual-Navigation, Installationsbefehle und Policy-Abdeckung im Diff gegen die Issues prüfen.
