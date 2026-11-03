# KonfiApp-QR-Code Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** KonfiApp-QR-Codes mit den erwarteten QR-Parametern erzeugen und `qrencode` im Docker-Image verfügbar machen.

**Architecture:** Der bestehende `QRService` bleibt der zentrale Generator. Die Docker-Laufzeit erhält das fehlende Debian-Paket; eine testbare Befehlszeilen-Erzeugung kapselt die festgelegten Optionen.

**Tech Stack:** PHP 8.4, PHPUnit 12, Debian Bookworm Docker Image, `qrencode`.

**Spec:** `docs/superpowers/specs/2026-11-03-konfiapp-qr-code-design.md`

## Global Constraints

- Der Payload wird vor der Kodierung weiterhin um abschließende Zeilenumbrüche bereinigt.
- QR-Version 2, Maske 4, Fehlerkorrektur M und Byte-Modus müssen fest eingestellt sein.
- `.env`, Laufzeitdaten und unbeteiligte Arbeitsstände bleiben unverändert.
- Vor dem Push laufen fokussierter Test, relevante Docker-Prüfung und `git diff --check`.

## Review Focus

- Fehlendes `qrencode` im Image: Dockerfile installiert das Paket und der Build kann die Binärdatei aufrufen.
- Falsche QR-Parameter: Unit-Test pinnt Version 2, Maske 4, M und Byte-Modus.
- Payload mit Zeilenumbruch: bestehende Bereinigung bleibt erhalten.
- Shell-Sonderzeichen in temporären Pfaden: Pfade bleiben escaped.

### Task 1: QR-Befehl testbar und KonfiApp-kompatibel machen

**Files:**
- Modify: `src/app/Services/QRService.php`
- Test: `src/tests/Unit/Services/QRServiceUnitTest.php`

- [ ] Failing test schreiben, der `QRService::buildCommand()` auf Version 2, Maske 4, M, Byte-Modus und escaped Pfade prüft.
- [ ] Den fokussierten Test ausführen und den erwarteten Fehlschlag beobachten.
- [ ] `buildCommand()` ergänzen und `generate()` darauf umstellen.
- [ ] Den fokussierten Test erneut ausführen und Erfolg prüfen.

### Task 2: Laufzeitabhängigkeit und Dokumentation ergänzen

**Files:**
- Modify: `Dockerfile`
- Modify: `docs/manual/administratorhandbuch/installation-docker.md`

- [ ] `qrencode` in die gemeinsame Debian-Paketliste aufnehmen.
- [ ] In der Docker-Installationsdokumentation die QR-Code-Laufzeitabhängigkeit ergänzen.
- [ ] Docker-Konfiguration statisch prüfen und, falls Docker verfügbar ist, im App-Image `qrencode --version` ausführen.

### Task 3: Abschlussprüfung und Veröffentlichung

- [ ] `git diff --check` und fokussierte PHP-Tests ausführen.
- [ ] Änderungen mit `fix(qr): KonfiApp-QR-Codes kompatibel erzeugen (#4).` committen; Issue #4 bleibt bis zum Real-Life-Scan offen.
- [ ] Den Commit nach `origin/main` pushen.
