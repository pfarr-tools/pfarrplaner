# Sentry-Exception-Reporting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Den konfigurierten Sentry-Host aus den produktiven Anwendungskomponenten erreichbar machen.

**Architecture:** Der Laravel-Exception-Callback in `bootstrap/app.php` und der eigene Exception-Handler bleiben unverändert. Ein separates nicht-internes Compose-Netzwerk wird an die Anwendungskomponenten mit Sentry-Reporting angebunden; ein Konfigurationstest prüft diese Eigenschaft.

**Tech Stack:** Laravel 13, PHP 8.4, Sentry Laravel 4.x, PHPUnit 12.

**Spec:** `docs/superpowers/specs/2026-11-03-sentry-exception-reporting-design.md`

## Global Constraints

- Produktionsgeheimnisse werden nicht in Repository-Dateien oder Testausgaben übernommen.
- 404-, Authentifizierungs- und Validierungsfehler bleiben von Sentry ausgeschlossen.
- PHPUnit läuft nur über die isolierte Testumgebung von `./planer dev test` oder mit explizit getrennter Testdatenbank.
- Vor Abschluss werden `git diff --check` und die relevanten Tests ausgeführt.

## Review Focus

- Unbehandelte Runtime-Ausnahme: wird an Sentry gemeldet.
- Bewusst ausgeschlossene Ausnahme: wird weiterhin nicht gemeldet.
- Produktions-DSN aus der Umgebung: bleibt unverändert und wird nicht protokolliert.

### Task 1: Regressionstest

**Files:**
- Modify: `src/tests/Feature/SentryErrorFeedbackFeatureTest.php`

- [ ] Einen Test für eine nicht erwartete Ausnahme im Laravel-Report-Pfad ergänzen.
- [ ] Den Test isoliert ausführen und den erwarteten Fehlschlag dokumentieren.

### Task 2: Produktions-Egress korrigieren

**Files:**
- Modify: `compose.production.yaml`
- Test: `scripts/tests/sentry_network_test.sh`

- [ ] Ein nicht internes Egress-Netzwerk definieren und an App, Horizon und Scheduler anbinden.
- [ ] Den Konfigurationstest zuerst fehlschlagen lassen und danach grün ausführen.
- [ ] Den Regressionstest und die bestehenden Sentry-Feature-Tests ausführen.

### Task 3: Abschlussprüfung

- [ ] Relevante PHPUnit-Tests über den isolierten Dev-Test-Befehl ausführen.
- [ ] Statische Shell-/Konfigurationsprüfungen für die Sentry-Umgebung ausführen.
- [ ] `git diff --check` ausführen und Änderungen auf geheime Werte prüfen.
