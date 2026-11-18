# Passwortloses Onboarding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entferne Passwortanmeldung und Passwortverwaltung und führe E-Mail-Link-Konten ohne Passkey/TOTP durch ein direkt nutzbares Sicherheits-Onboarding.

**Architecture:** Persistiere `isOfficialUser` und Erinnerungsmetadaten in der Benutzertabelle. Entferne Passwortpfade aus Authentifizierung und Administration. Ein Middleware-Gate schützt eine Inertia-Fullscreen-Seite, die Passkey- und TOTP-Setup anbietet; ein Sitzungsstatus gibt nach Einrichtung oder Aufschieben Zugriff frei.

**Tech Stack:** Laravel 11, PHP 8.4, Inertia.js, Vue 3, PHPUnit, Vitest, MariaDB/SQLite.

**Spec:** `docs/superpowers/specs/2026-11-18-passwordless-onboarding-design.md`

## Global Constraints

- Email-Link-Login verlangt weiterhin TOTP, wenn es aktiviert ist.
- Ein Passkey oder bestätigtes TOTP genügt als zusätzlicher Faktor.
- `isOfficialUser` wird aus dem bisherigen Vorhandensein eines Passwort-Hashes migriert.
- `must_change_password` wird für alle Benutzer:innen auf `false` gesetzt.
- Erinnerung: bei den ersten vier passenden E-Mail-Link-Logins, anschließend mindestens 14 Tage zwischen Hinweisen.
- Tests nutzen `./planer dev test` oder eine explizite SQLite-Testdatenbank.
- `docs/cve/` ist unversioniert und bleibt unangetastet.
- Jede Etappe wird getestet, senior-reviewed und separat auf diesem Feature-Branch committet.

## Review Focus

- Leerer Passwortwert ergibt bei Migration `isOfficialUser=false`; jede nichtleere Hash-Zeichenfolge ergibt `true`.
- E-Mail-Link-Login mit TOTP überspringt die Challenge nicht.
- Bereits passkey- oder TOTP-geschützte Konten erhalten keine Gate-Weiterleitung.
- Direkte URLs und XHR umgehen kein aktives Gate; Setup-Endpunkte bleiben erreichbar.
- Erste vier Erinnerungen und 14-Tage-Grenze funktionieren auch nach „Später erinnern“.

---

### Task 1: Kontoklassifikation und Passwortwechsel-Zwang ablösen

**Files:**
- Create: `src/database/migrations/*_persist_official_user_and_security_onboarding_state.php`
- Modify: `src/app/Models/People/User.php`
- Modify: `src/app/Http/Middleware/RequirePasswordChange.php` und `src/bootstrap/app.php`
- Test: `src/tests/Feature/UserWebFeatureTest.php` und Schema-Testbereich

**Interfaces:** `User::isOfficialUser` wird aus `is_official_user` gecastet. Persistenter Erinnerungsstatus besteht aus Login-Zähler und Zeitpunkt des zuletzt gezeigten Hinweises.

- [x] Schreibe Migrationstests für leere/nichtleere Passwörter, booleschen Cast und das Zurücksetzen aller `must_change_password`-Werte.
- [x] Führe diese Tests mit isolierter SQLite-Datenbank aus und bestätige zunächst den Fehler.
- [x] Implementiere additive Spalten, Backfill und Entfernung des Passwortwechsel-Gates.
- [x] Führe gezielte Tests, Formatprüfung und `git diff --check` aus; Senior Review; committe nur Task-1-Dateien.

### Task 2: Passwort-Authentifizierung und Administration entfernen

**Files:**
- Modify: `src/routes/web/Auth.php`, `src/routes/web/Home.php`, `src/routes/web/admin/User.php`
- Modify: `src/app/Http/Controllers/Auth/LoginController.php`, `EmailLoginLinkController.php`, `HomeController.php`, `UserController.php`
- Modify: `src/app/Http/Requests/UserRequest.php`, `src/app/Policies/UserPolicy.php`
- Modify: Login-, Demo-Login-, Admin-Usereditor- und Admin-Index-Vue-Seiten
- Test: Auth-, Kontoverwaltungs- und Passwortwechsel-Feature/Browsertests

- [x] Schreibe Tests, dass Passwort-Login, Reset/Änderung und Admin-Passwortsetzen nicht verfügbar sind; E-Mail-Link und Passkey bleiben nutzbar.
- [x] Entferne Passwortfelder/Aktionen aus der Administration; erhalte `isOfficialUser` und sende keine Passwortdaten in Requests oder Mail.
- [x] Aktualisiere Kontoanlage und Kontodaten-Mail auf E-Mail-Link-Anmeldung; entferne `must_change_password`-Weiterleitungen.
- [x] Führe betroffene Tests aus; Senior Review; committe nur Task-2-Dateien.

**Task-2-Verifikation:** 90 fokussierte PHP-Tests mit 550 Assertions und 5 Vitest-Tests der Recovery-Oberfläche bestanden. `format:check`, `git diff --check` und der Vite-Produktionsbuild bestanden. Kontoaktivierung folgt der vorhandenen Personenberechtigung (`update` beziehungsweise `create`); sie ist nicht auf Super-Admins beschränkt. Senior Review: **APPROVED**. Der umfassendere Route-Audit meldet bestehende, unveränderte öffentliche Mutation-Routen; der Review ordnet sie als separaten Sicherheits-Follow-up ein.

### Task 3: Login-Zähler und nicht umgehbares Middleware-Gate

**Files:**
- Create: `src/app/Services/SecurityOnboardingService.php`
- Create: `src/app/Http/Middleware/RequireSecurityOnboarding.php`
- Create: `src/app/Http/Controllers/Auth/SecurityOnboardingController.php`
- Create: `src/resources/js/Pages/Auth/SecurityOnboarding.vue` und gezielter UI-Test
- Modify: `src/app/Http/Controllers/Auth/EmailLoginLinkController.php`, `src/bootstrap/app.php`, Auth-/Settings-Routen
- Test: Service-/Middleware-/Featuretests und UI-Test in `src/tests/`

- [x] Schreibe Tests für vier erste E-Mail-Link-Logins, 14-Tage-Grenze, Faktor-Ausnahme, „Später erinnern“, gesperrte Direkt-URLs und nicht gleitende Anzeigedaten.
- [x] Aktualisiere den Zähler beim bestätigten E-Mail-Link-Login ohne zusätzlichen Faktor.
- [x] Schütze Web-, API- und Extranet-Routen; erlaube Onboarding, Faktor-Setup, Abmelden und Aufschieben.
- [x] Implementiere die Inertia-Onboarding-Seite mit direkter Nutzung der bestehenden Passkey-/TOTP-Einrichtung.
- [x] Führe isolierte Tests aus; Senior Review; committe nur Task-3-Dateien.

**Task-3-Verifikation:** Isolierte PHP-Featuretests: 15 Tests, 157 Assertions; Vitest: 13 Tests; Vite-Produktionsbuild erfolgreich. `format:check` für die neuen Vue-Dateien und `git diff --check` erfolgreich. Senior Re-Review: **APPROVED**. `docs:check` meldet weiterhin vorhandene fehlende PHPDocs in `InstallCommand.php` und `AuthBackend.php`.

### Task 4: Faktoreinrichtung im Onboarding verifizieren

**Files:**
- Modify: bestehende UI-/Featuretests für erfolgreiche und fehlgeschlagene Faktoreinrichtung

- [x] Ergänze UI-Tests, die TOTP- und Passkey-Setup direkt auf der Onboarding-Seite ausführen.
- [x] Prüfe Freigabe nach bestätigtem Faktor; unvollständiges TOTP darf die Sperre nicht lösen.
- [x] Führe gezielte PHP-/Vitest-Tests und Build aus; Senior Review; committe nur Task-4-Dateien.

**Task-4-Verifikation:** Isolierte PHP-Featuretests: 16 Tests, 170 Assertions; Vitest: 15 Tests; Vite-Produktionsbuild erfolgreich. Gezielte `format:check`- und `git diff --check`-Prüfungen erfolgreich. Senior Review: **APPROVED**.

### Task 5: Handbücher und Abschlussprüfung

**Files:** Benutzerhandbuch unter `docs/manual/benutzerhandbuch/`, Administratorenhandbuch unter `docs/manual/administratorhandbuch/`, Technisches Handbuch unter `docs/manual/technikhandbuch/` sowie `docs/admin/installation-und-migration.md`, dieser Plan.

- [x] Dokumentiere passwortlosen Login, Erinnerungsrhythmus, Admin-Kontoanlage und Migrations-/Rollback-Hinweise.
- [x] Führe Dokumentations-, Format-, Build- und Diff-Prüfungen aus.
- [x] Senior Review der Branch-Differenz; Issue #100 mit Umsetzung/Tests/Reviews/Commits aktualisieren; finaler Issue-Commit referenziert `Fixes #100`.

**Task-5-Verifikation:** `./planer dev manual:site` hat die gemeinsame Handbuchseite und alle drei PDF-Handbücher erfolgreich gebaut. Es erscheinen bekannte Warnungen zu nicht in der Navigation enthaltenen Seiten und bestehenden Querverweisen. `git diff --check` ist sauber. Senior Review und Re-Review: **APPROVED**; die gefundenen veralteten Passwort-Reset-Verweise in Berechtigungen, Startseite, Datenschutz, Voraussetzungen, Updatecheck und Stichwortverzeichnissen wurden bereinigt. `docs/cve/` bleibt unberührt.
