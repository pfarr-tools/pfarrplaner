# Öffentliche Opfererfassung Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Öffentliche, signierte Opfererfassung pro Kirchengemeinde, ein QR-Code für den Admin-Link, optionalen Urkundenversand und eine optionale Einzelaufstellung in der Opferurkunde bereitstellen.

**Architecture:** Eine eigene signierte City-Route liefert eine mobile Inertia-Seite und nimmt Beträge sowie optionale Opferzähler entgegen. Die vorhandenen Service-Felder bleiben die Datenquelle; ein authentifizierter PDF-Report rendert die Urkunde aus einem einzelnen Service.

**Tech Stack:** Laravel 11, PHP 8.4, Inertia/Vue, PHPUnit 11, Blade-PDF-Renderer, Docker-CLI über `./planer`.

**Spec:** `docs/superpowers/specs/2026-11-14-opfererfassung-design.md`

## Global Constraints

- Öffentliche GET-/POST-Zugriffe sind nur mit gültiger Laravel-Signatur möglich und benötigen keinen Login.
- Nur Services der signierten Gemeinde und des gewählten Datums dürfen gelesen oder geändert werden.
- Die bestehende Speicherung in `services.offering_amount`, `offerings_counter1` und `offerings_counter2` bleibt erhalten.
- Die optionale Denominationsaufstellung wird in `services.offering_denominations` als JSON gespeichert; die Urkunde druckt sie nur bei erfassten Stückzahlen und verwendet daraus die berechnete Summe.
- Die Opferurkunde ist eine einseitige A4-PDF; die Einzelaufstellung nach Schein- oder Münzsorten wird nur bei erfassten Stückzahlen ausgegeben.
- Eine konfigurierte Gemeinde-E-Mail-Adresse löst nach erfolgreicher Erfassung den Versand der erzeugten Opferurkunde als PDF-Anhang aus.
- Tests verwenden ausschließlich die isolierte SQLite-Testdatenbank.
- PHP-/Vue-Deklarationen erhalten aussagekräftige PHPDoc-/JSDoc-Blöcke und deutsche Benutzertexte.

## Review Focus

- Ungültige oder manipulierte Signaturen dürfen weder Seite noch Speichervorgang zulassen — Feature-Test in Task 1.
- Ein Service einer anderen Gemeinde darf nicht über die signierte URL geändert werden — Feature-Test in Task 1.
- Leerer aktueller Tag muss zeitlich passende Services derselben Gemeinde anbieten — Feature-Test in Task 1.
- Deutsche Betragsnotation und ungültige Beträge müssen serverseitig korrekt validiert werden — Feature-Test in Task 1.
- Ohne erfasste Stückzahlen darf die Urkunde keine Einzelaufstellung ausgeben; mit Stückzahlen muss sie diese sichtbar übernehmen — Report-Test/Renderprüfung in Task 3.

---

### Task 1: Signierte öffentliche Opfererfassung

**Files:**
- Modify: `src/routes/web/City.php`
- Modify: `src/app/Http/Controllers/CityController.php`
- Create: `src/tests/Feature/PublicOfferingsFeatureTest.php`

**Interfaces:**
- Produces route names `offerings.public` (GET) and `offerings.save` (POST), both accepting a city route parameter and requiring Laravel's `signed` middleware.
- Produces controller methods that expose current-day services and a seven-day temporal fallback, and persist only services belonging to the selected city. The save contract accepts optional `offering_denominations` keyed by the 13 fixed cent values; the server derives and stores the total.

- [ ] **Step 1: Write failing feature tests** for guest access with a valid signed URL, invalid signatures, current-day services, seven-day fallback, cross-city rejection, amount validation, saving optional counter names, and server-side denomination-sum calculation.
- [ ] **Step 2: Run the focused feature test** with `./planer dev test tests/Feature/PublicOfferingsFeatureTest.php` and verify it fails because the routes/controller behavior does not exist.
- [ ] **Step 3: Implement the migration/model field plus signed routes and controller behavior** with explicit City/Service queries, Europe/Berlin date boundaries, normalized German decimal amounts, fixed denomination values, and authorization-independent signature validation.
- [ ] **Step 4: Re-run the focused feature test** and verify all cases pass.

### Task 2: Admin link and public mobile UI

**Files:**
- Modify: `src/resources/js/Pages/Admin/City/Editor.vue`
- Create: `src/resources/js/Pages/Public/City/Offerings.vue`
- Create: `src/tests/js/Pages/Public/City/Offerings.test.js`

**Interfaces:**
- Consumes the `city`, `services`, `fallback`, `showCounters` and signed save URL payload from Task 1.
- Produces a visible signed-link control and QR code in the City → Opfer tab, plus an optional email field for automatic certificate delivery. The mobile public form submits `service_id`, optional `offering_denominations`, and optional counter fields. The denomination editor is collapsed by default and computes the total reactively.

- [ ] **Step 1: Write failing Vue tests** for service selection, optional counter-field toggle, denomination editor expansion, automatic total calculation, amount submission, fallback rendering, signed-link display and QR-code rendering.
- [ ] **Step 2: Run the focused Vue test** with the repository's Jest/Vitest command and verify it fails for the missing page behavior.
- [ ] **Step 3: Implement the public page** without `admin-layout`, with accessible labels, German currency input, success feedback, and responsive cards; add the signed URL to the admin tab without exposing secrets beyond the signed URL itself.
- [ ] **Step 4: Format and re-run the focused Vue test** through the Docker tooling and verify it passes.

### Task 3: Opferurkunde report

**Files:**
- Create: `src/app/Reports/OfferingCertificateReport.php`
- Create: `src/resources/views/reports/offeringcertificate/render.blade.php`
- Create: `src/app/Mail/OfferingCertificateMail.php`
- Create: `src/tests/Feature/OfferingCertificateReportFeatureTest.php`

**Interfaces:**
- Consumes an authenticated request parameter `service` identifying an existing Service with an entered offering amount.
- Produces a downloadable A4 PDF titled `Opferurkunde` containing city, date, service type/title, organists, offering goal, amount, and counter names where available; it includes the denomination table only when stored counts exist. The mail class attaches the same rendered PDF.

- [ ] **Step 1: Write failing report tests** for report discovery, authenticated rendering, missing/foreign service rejection, required certificate fields, omission of an empty denomination table, rendering stored denomination counts when present, and queued delivery to the configured city email.
- [ ] **Step 2: Run the focused report test** in the isolated Docker test environment and verify the expected failures.
- [ ] **Step 3: Implement the report class, Blade template and queued Mailable** following existing PDF report conventions and the supplied issue attachment's A4 visual hierarchy; include denomination rows only when counts exist.
- [ ] **Step 4: Render a representative PDF**, inspect text and the PNG page visually, then re-run the report tests.

### Task 4: Documentation and integrated verification

**Files:**
- Modify: `docs/manual/benutzerhandbuch/administration.md`
- Modify: `docs/manual/benutzerhandbuch/berichte.md`
- Modify: `docs/manual/administratorhandbuch/berechtigungen.md`
- Modify: `docs/manual/technisches-handbuch/*` (only the relevant section/file)

- [ ] **Step 1: Document** where administrators find the signed link, how the public entry works, and where the authenticated Opferurkunde report is generated.
- [ ] **Step 2: Run `git diff --check`.**
- [ ] **Step 3: Run the relevant Docker quality checks** (`./planer dev format:check`, focused tests, and `./planer dev docs:check`) without modifying unrelated generated artifacts.
- [ ] **Step 4: Inspect the final diff and issue state**, then report any unrelated pre-existing failures separately.
