# Mail-Layout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** HTML-Mails für Issue #65 konsistent, gebrandet und Outlook-kompatibel rendern.

**Architecture:** Die bestehenden Markdown- und Service-Mailpfade bleiben erhalten. Ihre wiederverwendbaren Layout- und Theme-Dateien werden auf dieselbe tabellenbasierte Pfarrplaner-Gestaltung mit inline-fähigen Styles ausgerichtet.

**Tech Stack:** Laravel 11 Markdown Mailables, Blade, PHPUnit 12, Docker über `./planer`.

**Spec:** `docs/superpowers/specs/2026-11-11-mail-layout-design.md`

## Global Constraints

- Fachliche Mailinhalte, Links und iCalendar-Anhänge nicht verändern.
- Tests ausschließlich mit der isolierten Testdatenbank von `./planer dev test` beziehungsweise expliziter SQLite-`:memory:`-Konfiguration.
- Keine SMTP-, Queue- oder Mailserveränderungen.
- Deutsche Prosa mit korrekten Umlauten und `ß`; Blade- und PHP-Regeln des Repositorys einhalten.

## Review Focus

- Markdown-Mail ohne externe Stylesheets muss Branding und Kartenrahmen inline enthalten: Render-Test in `AbsenceMailUnitTest`.
- Button-, Panel- und Footer-Komponenten müssen auch in Outlook-tauglichen Tabellen bleiben: strukturelle Prüfung der Vendor-Komponenten und Render-Test.
- Eigene Service-Mails müssen denselben Außenrahmen erhalten, ohne Fachinhalt oder Anhänge zu verlieren: Render-Test in `ServiceMailUnitTest`.

### Task 1: Failing render coverage

**Files:**
- Modify: `src/tests/Unit/Mail/AbsenceMailUnitTest.php`
- Modify: `src/tests/Unit/Mail/ServiceMailUnitTest.php`

- [x] **Step 1: Write the failing tests** for branded Markdown and service-mail rendering.
- [x] **Step 2: Run the isolated tests** and confirm the new branding assertion fails against the current templates.

### Task 2: Shared HTML mail presentation

**Files:**
- Modify: `src/resources/views/vendor/mail/html/layout.blade.php`
- Modify: `src/resources/views/vendor/mail/html/header.blade.php`
- Modify: `src/resources/views/vendor/mail/html/footer.blade.php`
- Modify: `src/resources/views/vendor/mail/html/button.blade.php`
- Modify: `src/resources/views/vendor/mail/html/panel.blade.php`
- Modify: `src/resources/views/vendor/mail/html/subcopy.blade.php`
- Modify: `src/resources/views/vendor/mail/html/themes/default.css`
- Modify: `src/resources/views/mail/layout/default.blade.php`
- Modify: `src/resources/views/mail/layout/blocks/content-table.blade.php`

- [x] **Step 1: Implement** the shared branded, table-based layout with inline critical styles and responsive media rules.
- [x] **Step 2: Run** the focused mail tests and confirm they pass.
- [x] **Step 3: Run** Blade formatting and `git diff --check`.

### Task 3: Documentation and final verification

**Files:**
- Review: `docs/mail.md`
- Review: `docs/manual/administratorhandbuch/mail.md`

- [x] **Step 1:** Confirm no operator-facing behavior or configuration changed; update documentation only if required.
- [x] **Step 2:** Run relevant Docker tests, format checks, analysis/lint/docs checks as available.
- [ ] **Step 3:** Read back the final diff, commit with `fix:`, comment and close Issue #65, then push `main`.
