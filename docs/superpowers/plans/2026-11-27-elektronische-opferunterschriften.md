# Elektronische Opferunterschriften Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Die externe Opfereingabe kann elektronische Unterschriften erfassen und eine signierte, archivierte und revisionsfähige Opferurkunde erzeugen.

**Architecture:** Die bestehende `Service`-Opfereingabe bleibt die Quelle der unbestätigten Eingaben. Eine neue revisionsbezogene Opferabrechnung speichert Snapshot, Signatur-Metadaten, Manifest und archivierte PDF außerhalb des öffentlich zugänglichen Storages. Die Finalisierung wird über einen dedizierten Service in einer Transaktion durchgeführt; unsignierte Gemeinden verwenden weiterhin den bestehenden Ablauf.

**Tech Stack:** Laravel 11, PHP 8.4, MariaDB/SQLite-Tests, Laravel Storage, Inertia/Vue 3, Vitest, Chromium-PDF über `App\Documents\PDF`.

**Spec:** `docs/superpowers/specs/2026-11-27-elektronische-opferunterschriften-design.md`

## Global Constraints

- Signaturen sind einfache elektronische Signaturen, keine QES.
- Es werden nur fertige PNG-Signaturen gespeichert, keine biometrischen Rohdaten.
- Öffentliche signierte Links dürfen nach Finalisierung nicht erneut verändern.
- Private Storage-Pfade und bestehende Mandanten-/Berechtigungsgrenzen bleiben erhalten.
- Bestehende unsignierte Opfereingaben und historische Opferurkunden bleiben kompatibel.
- Alle öffentlichen PHP- und JavaScript-Deklarationen erhalten aussagekräftige Dokumentation.
- Tests und Qualitätswerkzeuge laufen über Docker und `./planer`.

## Review Focus

- Canvas-Resize und Geräteorientierung dürfen eine sichtbare Signatur weder löschen noch verzerren; Test in Task 1.
- Eine manipulierte oder übergroße Data-URL darf keine Datei oder Revision erzeugen; Test in Task 2.
- Wiederholte Finalisierung darf keinen zweiten Beleg erzeugen; Test in Task 3.
- Ein Service aus einer fremden Gemeinde darf weder signiert noch heruntergeladen werden; Test in Task 4.
- Fehler bei PNG- oder PDF-Speicherung dürfen keine als final markierte Revision hinterlassen; Test in Task 3.

### Task 1: Wiederverwendbares Signaturfeld und externe Eingabe

**Files:**
- Create: `src/resources/js/Components/Forms/SignaturePad.vue`
- Modify: `src/resources/js/Pages/Public/City/Offerings.vue`
- Test: `src/tests/js/components/Forms/SignaturePad.test.js`
- Test: `src/tests/js/Pages/Public/City/Offerings.test.js`
- Modify: `src/package.json`, `src/package-lock.json`

**Interfaces:**
- Produces component props `modelValue`, `label`, `required` and emits `update:modelValue`, `changed`.
- Produces a submission payload with `signatures: [{name: string, image: string}]`.

- [ ] Write failing component tests for pointer input, clear, required empty state and resize preservation.
- [ ] Run the focused Vitest file and verify failure because the component and props do not exist.
- [ ] Add `signature_pad` and implement the component with DPR-aware canvas resizing and redraw from `toData()`.
- [ ] Add one pad per non-empty counter name, reset pads when amount/denominations/names/service change, and block submission until every required pad is non-empty.
- [ ] Run focused JS tests and the existing offerings tests.
- [ ] Run `./planer dev format:check` for changed JS/package files and commit the task.

### Task 2: Datenmodell, Konfiguration und sichere Signaturvalidierung

**Files:**
- Create: `src/database/migrations/*_add_electronic_offering_signatures.php`
- Create: `src/app/Models/OfferingSettlement.php`
- Create: `src/app/Services/OfferingSignatureService.php`
- Modify: `src/app/Models/Places/City.php`
- Modify: `src/app/Http/Controllers/CityController.php`
- Test: `src/tests/Unit/Services/OfferingSignatureServiceTest.php`
- Test: `src/tests/Feature/PublicOfferingsFeatureTest.php`

**Interfaces:**
- `OfferingSignatureService::validateImage(string $dataUrl): string` returns a validated PNG data payload or throws a validation exception.
- `OfferingSignatureService::canonicalSnapshot(Service $service, array $signatures): array` returns stable, sorted snapshot data.
- `City::electronic_offering_signatures` is a boolean configuration field.
- `OfferingSettlement` stores `service_id`, `revision`, `snapshot`, `signatures`, `manifest`, `status`, `finalized_at`, and archive paths/hashes.

- [ ] Write failing tests for configuration exposure, malformed/oversized/non-PNG signatures, canonical ordering and service/city ownership.
- [ ] Run the focused PHP tests through `./planer dev test` and verify the expected missing-class/field failures.
- [ ] Add migration, model casts/fillable relations and a service that validates PNG magic bytes, dimensions and size without logging image content.
- [ ] Add signed-link payload handling and enforce the configured required-signature count server-side.
- [ ] Run focused feature/unit tests and migration rollback checks.
- [ ] Run Docker PHP quality checks relevant to changed files and commit the task.

### Task 3: Atomare Finalisierung, Manifest und Archiv-PDF

**Files:**
- Create: `src/app/Services/OfferingSettlementFinalizer.php`
- Create: `src/routes/web/OfferingSettlement.php`
- Modify: `src/app/Http/Controllers/CityController.php`
- Modify: `src/app/Reports/OfferingCertificateReport.php`
- Modify: `src/resources/views/reports/offeringcertificate/render.blade.php`
- Modify: `src/app/Mail/OfferingCertificateMail.php`
- Test: `src/tests/Feature/OfferingSettlementFinalizationFeatureTest.php`

**Interfaces:**
- `OfferingSettlementFinalizer::finalize(Service $service, array $signatures): OfferingSettlement` is idempotent for the current service revision.
- `OfferingSettlementFinalizer::verify(OfferingSettlement $settlement): bool` verifies manifest, snapshot, signatures and archived PDF hashes.
- Archive paths are private and derived from settlement UUID/revision, never from user input.

- [ ] Write failing tests for snapshot/hash/manifest creation, signed PDF output, idempotent repeat submission and rollback on storage/PDF failure.
- [ ] Run the focused feature test and verify failure before finalizer implementation.
- [ ] Implement transaction-scoped finalization, HMAC-signed canonical manifest using an encrypted/configured key identifier, private PNG storage and archived PDF storage.
- [ ] Extend the certificate view with confirmation text, names, images, timestamp, receipt ID, revision and manifest reference.
- [ ] Add an authorized archived-download endpoint and make certificate reports prefer the archive.
- [ ] Run the focused feature tests, PDF checks and existing certificate/mail tests.
- [ ] Run `git diff --check` and commit the task.

### Task 4: Revisionierung, Änderungsschutz und Dokumentation

**Files:**
- Create: `src/app/Services/OfferingSettlementRevisionService.php`
- Modify: `src/app/Http/Controllers/CityController.php`
- Modify: `src/app/Models/OfferingSettlement.php`
- Modify: `docs/admin/installation-und-migration.md`
- Modify: relevant user/technical handbook pages
- Test: `src/tests/Feature/OfferingSettlementRevisionFeatureTest.php`

**Interfaces:**
- `OfferingSettlementRevisionService::create(Service $service, string $reason, User $user): Service` creates a new revision and preserves the old settlement.
- Finalized services reject direct changes to all certificate-confirmed offering fields; correction routes require an authenticated authorized user and reason.

- [ ] Write failing tests for direct mutation rejection, reason/auditor/timestamp recording, preserved previous archive and renewed signature requirement.
- [ ] Implement server-side guards and explicit authorized correction flow.
- [ ] Document configuration, key rotation, backup/restore and correction workflow in the appropriate German manuals.
- [ ] Run all focused tests plus existing public-offerings, certificate and City tests.
- [ ] Run `./planer dev quality`, inspect the complete diff and commit the task.

### Task 5: Gesamtverifikation und Übergabe

- [ ] Run `./planer dev format:check`, `./planer dev analyse`, `./planer dev taint:check`, `./planer dev lint`, `./planer dev docs:check` and `./planer dev quality` as applicable.
- [ ] Run the complete relevant PHP and JS test suites with isolated test configuration.
- [ ] Perform a final self-review against the issue acceptance criteria and record any unsupported browser/device checks explicitly.
- [ ] Comment implementation, commit and verification details on issue #154, read the comment back, and only then close the issue if all acceptance criteria are verified.
