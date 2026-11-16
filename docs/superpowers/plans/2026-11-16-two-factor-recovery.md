# Super-Admin-2FA-Wiederherstellung Implementierungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Schritte sind mit Checkboxen (`- [ ]`) nachzuverfolgen.

**Goal:** Ein Super-Admin kann nach frischer Passwortbestätigung und dokumentierter Identitätsprüfung den verlorenen 2FA-Faktor widerrufen; das Zielkonto bleibt bis zur bestätigten TOTP-Neueinrichtung eingeschränkt.

**Architecture:** Eine eigene autorisierte Recovery-Action sperrt das Zielkonto, löscht Faktor- und offene Challenge-Daten, setzt einen serverseitig ausgewerteten Recovery-Marker und schreibt eine unveränderliche Audit-Zeile. Ein Web-/API-Middleware-Gate lässt für markierte Konten nur die dedizierte TOTP-Neueinrichtung und Logout zu; die Bestätigung löscht den Marker. Datenbank-Sitzungen und persönliche API-Tokens werden widerrufen; eine Admin-Oberfläche sammelt Passwort und Supportfall-Referenz und zeigt die Audit-Historie.

**Tech Stack:** Laravel 13 / PHP 8.4, Eloquent, Datenbankmigrationen, Inertia.js/Vue, PHPUnit, Vitest, `./planer`-Docker-Kommandos.

**Spec:** `docs/superpowers/specs/2026-11-16-two-factor-recovery-design.md`

## Global Constraints

- Nur Super-Admins dürfen Recovery auslösen; Selbst-Recovery ist verboten.
- Aktuelles Super-Admin-Passwort wird direkt vor der Recovery erneut geprüft und rate-limitiert.
- Kein Passwortreset und kein E-Mail-Versand sind Teil der Aktion.
- Das Zielkonto bleibt bis zur bestätigten TOTP-Neueinrichtung auf Wiederherstellungsrouten beschränkt.
- Datenbank-Sitzungstreiber ist Voraussetzung; Sessions und persönliche API-Tokens des Zielkontos werden widerrufen.
- Audit speichert Actor-ID, Ziel-ID, Supportfall-Referenz, Zeitpunkt und IP, niemals Passwörter, TOTP-Secrets, Recovery-Codes oder Login-Codes.
- Die Recovery-Sicherheitsmigration ist vorwärtsgerichtet und verweigert destruktiven Rollback; Benutzer-IDs folgen dem vorhandenen `unsignedInteger`-Schema.
- Tests laufen ausschließlich mit `./planer dev test` oder mit ausdrücklich isolierter Testdatenbank; niemals Dev-/Produktionsdaten verwenden.
- Jede Implementierungsetappe erhält Senior Review, Fehlerkorrektur, erneute Verifikation und einen eigenen Commit.
- Benutzer-, Administratoren-, Technisches Handbuch sowie Betriebsdokumentation werden mit der betroffenen Etappe aktualisiert.

## Review Focus

- Ein normales Admin-Konto oder ein Super-Admin, der sich selbst auswählt, darf keine Recovery ausführen — Test in Task 2.
- Falsches, fehlendes oder zu oft eingereichtes Super-Admin-Passwort darf weder Faktorstatus noch Audit verändern — Tests in Task 2.
- Pending- und bestätigte TOTP-Zustände sowie offene/abgelaufene E-Mail-Codes müssen nach der Recovery unwirksam sein — Tests in Task 2.
- Ein markiertes Konto darf Web- und API-Funktionen erst nach TOTP-Bestätigung wieder nutzen; Recovery-Seite und Logout bleiben erreichbar — Tests in Task 3.
- Session-Treiber außerhalb `database`, Datenbankfehler und gleichzeitige Recovery-/TOTP-Requests dürfen keinen halben erfolgreichen Reset erzeugen — Tests in Tasks 2–3.

---

### Task 1: Recovery-Audit und Statusmigration

**Files:**
- Create: `src/database/migrations/2026_11_16_000001_add_two_factor_recovery_state_and_audit_table.php`
- Create: `src/app/Models/People/TwoFactorRecoveryAudit.php`
- Test: `src/tests/Feature/TwoFactorRecoveryFeatureTest.php`
- Modify: `src/app/Console/Commands/DevBuilder/DemoBuilder.php`
- Modify: `src/tests/Feature/TwoFactorUserIntegrationFeatureTest.php`

**Interfaces:**
- Produces `TwoFactorRecoveryAudit::query()` with fields `actor_user_id`, `target_user_id`, `support_reference`, `ip_address`, and timestamps.
- Adds nullable `users.two_factor_recovery_required_at` timestamp.
- Audit IDs are unsigned integers without cascading user foreign keys, preserving actor/target IDs if an account is later removed.

- [x] **Step 1: Write failing migration/model tests** asserting the recovery marker column, audit fields/indexes, forward-only migration behavior, account-deletion retention, and `DemoBuilder` clears the marker and audit rows in demo data.
- Test names: `test_recovery_schema_and_audit_model_are_ready()` and `test_demo_builder_clears_recovery_markers_and_audits()`.
- [x] **Step 2: Run the isolated tests and confirm they fail** with missing table/column behavior.
- [x] **Step 3: Implement the forward-only migration and documented model** with explicit fillable/relations; extend demo cleanup.
- [x] **Step 4: Run** `./planer dev test --no-browser --no-js`; expect all PHP tests to pass with only documented skips.
- [x] **Step 5: Run Pint for the changed PHP files and `git diff --check`.**
- [x] **Step 6: Senior Review Task 1; fix findings and repeat the relevant checks.**
- [x] **Step 7: Commit** as `feat(auth): Audit für 2FA-Wiederherstellungen ergänzen` (`52e1c305`).

### Task 2: Autorisierte, re-authentifizierte Recovery-Action

**Files:**
- Create: `src/app/Actions/Security/RecoverUserTwoFactor.php`
- Create: `src/app/Http/Requests/RecoverTwoFactorRequest.php`
- Modify: `src/app/Http/Controllers/UserController.php`
- Modify: `src/app/Policies/UserPolicy.php`
- Modify: `src/routes/web/User.php`
- Test: `src/tests/Feature/TwoFactorRecoveryFeatureTest.php`

**Interfaces:**
- Route: `POST /benutzer/{user}/zwei-faktor/wiederherstellen`, name `user.two-factor.recover`.
- Controller: `UserController::recoverTwoFactor(RecoverTwoFactorRequest $request, User $user, RecoverUserTwoFactor $recovery): RedirectResponse`.
- Action: `RecoverUserTwoFactor::execute(User $actor, User $target, string $currentPassword, string $supportReference, string $ipAddress): TwoFactorRecoveryAudit`.
- Policy ability: `recoverTwoFactor(User $actor, User $target): bool`, true only for Super-Admin and when actor and target differ.
- Request accepts `current_password` and an ASCII support reference of 1–100 letters, digits, dots, underscores or hyphens; reject whitespace-only or free-form narratives.

- [x] **Step 1: Write failing feature tests** for authorization/self-recovery, active and pending factors, password re-authentication/rate limits, factor state reload, consumed challenges/secret-free audit, database sessions, token/session revocation, and transaction rollback.
- [x] **Step 2: Run** `./planer dev test --no-browser --no-js`; initial RED run failed at the absent named recovery route as expected.
- [x] **Step 3: Implement policy and FormRequest**; check the actor password with `Hash::check`, apply a per-actor limit of five failures per minute before expensive work, and never log password input.
- [x] **Step 4: Implement the Action** in one transaction: re-authenticate and lock actor, then lock/reload target; require active or pending TOTP; clear both factor states with package-supported model methods; mark all unconsumed email challenges consumed; set `two_factor_recovery_required_at`; insert the audit row. Keep password and `must_change_password` unchanged.
- [x] **Step 5: Reject the operation unless `config('session.driver') === 'database'`; require the session connection to match the app DB; revoke Sanctum tokens and target sessions, and fail without claiming successful recovery.**
- [x] **Step 6: Re-run** isolated feature tests, full `./planer dev test --no-browser --no-js`, Pint, PHPStan, and `git diff --check`.
- [x] **Step 7: Senior Review Task 2; fix findings and repeat checks.** Review accepted Task 2 scope with the recovery-marker gate explicitly deferred to Task 3; the branch remains unpublished until Task 3 and full feature verification.
- [x] **Step 8: Commit** as `feat(auth): Super-Admins können verlorene 2FA widerrufen`.

### Task 2a: Challenge-Fremdschlüssel mit migrierter Benutzer-ID abgleichen

Der angeforderte Produktions-Pull brach im lokalen Vorab-Migrationsschritt ab:
Die seit `2025_12_30_092052_migrate_int_ids` verwendete `users.id` ist auf
MariaDB `BIGINT UNSIGNED`, während die neue Challenge-Tabelle zunächst
`user_id` als `INT UNSIGNED` anlegte.

- [x] **Step 1: Reproduce** the failed foreign-key migration on the local Dev MariaDB and inspect the actual `users.id` type.
- [x] **Step 2: Align** `two_factor_login_challenges.user_id` with `users.id` as `unsignedBigInteger`.
- [x] **Step 3: Apply the pending migration on Dev and verify the resulting foreign-key types.** After removing the empty, unregistered partial table from the failed attempt, both pending migrations completed; MariaDB reports `two_factor_login_challenges.user_id` as `bigint(20) unsigned` with a foreign key to `users`.
- [x] **Step 4: Senior Review the migration correction and verify findings.** Final re-review approved with no findings.
- [x] **Step 5: Commit** as `fix(auth): Challenge-Fremdschlüssel an Bigint-ID anpassen.` (`7a289f7f`).
- [x] **Step 6: Retry the authorized production-to-Dev data pull and verify migration completion, app health and imported objects.** Dump and 1.36 GiB object transfer completed; after removing two empty, unregistered local-only tables, both branch migrations ran. Healthcheck returned `db=connected`; Dev contains 796 users, 6,881 services and 3,614 attachment objects.

### Task 2b: Dev-Daten-Pull mit Quellschema abgleichen

Beim ersten Pull mit dem Issue-80-Branch blieb die lokal vorab migrierte
Challenge-Tabelle stehen, weil der Produktionsdump sie noch nicht enthielt.
Der anschließende Migrationlauf konnte die Tabelle deshalb nicht neu anlegen.

- [x] **Step 1: Add a failing script regression** requiring the Dev pull to wipe its target before restore and migrate after the source dump is restored.
- [x] **Step 2: Change only the development pull path** to clear the local schema before importing and apply current branch migrations after import; leave the production-target path unchanged.
- [x] **Step 3: Document** the Dev pull replacement and post-restore migration behavior in the operator handbook.
- [x] **Step 4: Run the script regression and relevant operational checks.** `bash scripts/tests/data_sync_test.sh`, `bash -n scripts/data-sync.sh scripts/tests/data_sync_test.sh`, and `git diff --check` passed. The already completed data import was verified separately; no second 1.36 GiB transfer was needed.
- [x] **Step 5: Senior Review the script, test, and operator documentation; fix findings and re-review.** Initial reviews found and resolved conflicting target modes, unverified DB destination, source-dump staging, flaky pipeline ordering assertions, failure to stop when `--source-down` fails, and ineffective negative test assertions. Final Senior Review: APPROVED, no remaining findings: [review](t3-thread://v2/884d3774-4427-4d3d-945d-0bbee874384d/thread:delegated-task:command%3Amcp%3A1e0a1d41-f388-4ace-a2a5-9f442f325a47%3Adelegate-task%3A6c35491f-92b9-4a1c-8901-cde282e26380).
- [x] **Step 6: Commit** as `fix(sync): Dev-Pull nach dem Import migrieren.`
- [x] **Step 7: Record the already completed source-to-Dev import and verify it remains healthy.** Healthcheck returned `db=connected`; Dev still contains the imported users/services and 3,614 attachment objects.

### Task 3: Serverseitige Sperre bis zur TOTP-Neueinrichtung

**Files:**
- Create: `src/app/Http/Middleware/RequireTwoFactorRecovery.php`
- Create: `src/app/Http/Controllers/Auth/TwoFactorRecoveryController.php`
- Create: `src/resources/js/Pages/Profile/TwoFactorRecovery.vue`
- Modify: `src/bootstrap/app.php`
- Modify: `src/app/Http/Controllers/UserController.php`
- Modify: `src/routes/web/User.php`
- Test: `src/tests/Feature/TwoFactorRecoveryFeatureTest.php`
- Test: `src/tests/js/Pages/Profile/TwoFactorRecovery.test.js`

**Interfaces:**
- Named GET route `user.two-factor.recovery` renders the dedicated setup page.
- Middleware redirects marker-bearing authenticated users to that route; it permits only that page, TOTP start/confirm routes, logout and the existing mandatory password-change route when applicable.
- The same guard runs for authenticated API requests; guests and normal accounts are unaffected.
- Successful confirmation clears `two_factor_recovery_required_at` on the same locked user row and transaction that confirms TOTP.

- [x] **Step 1: Write failing tests** `test_recovered_account_can_only_open_reenrolment_and_logout_routes()`, `test_recovered_account_cannot_call_authenticated_api_routes()`, `test_failed_or_incomplete_totp_confirmation_keeps_recovery_gate()`, and `test_successful_totp_confirmation_releases_recovery_gate()`; include guest, normal account, and simultaneous mandatory-password-change coverage.
- [x] **Step 2: Run** isolated PHP and focused Vue tests and confirm the gate tests fail before implementation; RED logs are recorded in the SDD directory.
- [x] **Step 3: Implement middleware and route registration** in web/API middleware groups, ordered after authentication, with route-name allowlisting rather than broad URL-prefix exceptions.
- [x] **Step 4: Clear the recovery marker only after successful TOTP confirmation** under the existing user-row lock; recovery failure must leave the account gated.
- [x] **Step 5: Implement the dedicated page** with current-password entry, TOTP setup and confirmation, accessible errors, and no unrelated profile data or secret persistence after confirmation.
- [x] **Step 6: Run full PHP/Vue suites, Pint, format check, PHPStan, lint and `git diff --check`; record the unchanged `docs:check` baseline.**
- [x] **Step 7: Senior Review Task 3; fix findings and repeat checks.** Approved with no findings.
- [x] **Step 8: Commit** as `feat(auth): 2FA-Wiederherstellung bis zur Neueinrichtung sperren` (`f35e637c`).

### Task 4: Super-Admin-Oberfläche und Audit-Historie

**Files:**
- Modify: `src/app/Http/Controllers/UserController.php` (user editor data)
- Modify: `src/app/Http/Middleware/HandleInertiaRequests.php` (share flash type)
- Modify: `src/resources/js/Pages/Admin/User/UserEditor.vue`
- Create: `src/resources/js/Pages/Admin/User/TwoFactorRecoveryPanel.vue`
- Modify: `src/app/Models/People/TwoFactorRecoveryAudit.php` (typed actor relations)
- Test: `src/tests/Feature/TwoFactorRecoveryFeatureTest.php`
- Test: `src/tests/Feature/UserWebFeatureTest.php`
- Test: `src/tests/Feature/HandleInertiaRequestsFeatureTest.php`
- Test: `src/tests/js/Pages/Admin/User/TwoFactorRecoveryPanel.test.js`

**Interfaces:**
- Only a Super-Admin editing another account sees the recovery action and audit history.
- Confirmation requires the Super-Admin's current password and support reference; copy states that prior identity verification is required and the target must reenroll TOTP.
- History shows timestamp, actor display name/ID, and support reference; it never shows factor material or authentication input.

- [x] **Step 1: Write failing PHPUnit/Vitest tests** `test_only_super_admin_receives_recovery_action_and_history()`, `test_recovery_history_contains_actor_time_and_reference_only()`, and Vue cases for password/reference fields, cancel/submit, success/error feedback, and no secret rendering.
- [x] **Step 2: Run the focused Vue and isolated feature cases**; verify missing history props and component failures before implementation.
- [x] **Step 3: Implement admin editor data and confirmation flow** using the Task 2 route; never include password or factor secrets in page props. Return to the target editor and render typed Inertia flash feedback.
- [x] **Step 4: Run** the full PHP suite and full Vue suite; run Pint/Prettier, PHPStan, ESLint on new Vue files, and `git diff --check`.
- [x] **Step 5: Senior Review Task 4; fix findings and repeat checks.** Final re-review approved with no findings.
- [x] **Step 6: Commit** as `feat(auth): 2FA-Recovery in der Benutzerverwaltung anzeigen`.

### Task 4a: Recovery-Angebot an Laufzeitvoraussetzungen koppeln

Nach dem Task-4-Review wurde festgestellt, dass die Oberfläche die Aktion auch
ohne wiederherstellbaren Faktor oder ohne widerrufbare Datenbank-Sitzungen
anbot. Diese Korrektur ergänzt den berechtigten Verlauf um einen Hinweis, wenn
die Sitzungs-Konfiguration die Wiederherstellung verhindert.

- [x] **Step 1: Add failing feature tests** for unsupported session driver, mismatched session connection, and targets without an active or pending factor.
- [x] **Step 2: Gate the recovery action** on the server-side preconditions and retain authorized audit history visibility.
- [x] **Step 3: Verify focused PHP/Vue tests and format checks.**
- [x] **Step 4: Senior Review Task 4a; fix findings and repeat affected checks.** First review found a stale open form and non-isolated negative tests; the fixes and regression tests passed. Re-review approved with no findings.
- [x] **Step 5: Commit** as `fix(auth): Recovery-Angebot an Voraussetzungen koppeln.`

### Task 5: Handbücher, Betriebsgrenze und Gesamtprüfung

**Files:**
- Modify: `docs/manual/benutzerhandbuch/einstellungen.md`
- Modify: `docs/manual/administratorhandbuch/sicherheit.md`
- Modify: `docs/manual/technikhandbuch/sicherheitsaudit.md`
- Modify: `docs/admin/installation-und-migration.md`
- Modify: `docs/superpowers/plans/2026-11-16-two-factor-recovery.md`
- Modify: `.superpowers/sdd/2026-11-16-two-factor-recovery/progress.md`
- Modify: `.superpowers/sdd/2026-11-16-two-factor-recovery/task-5-brief.md`

- [x] **Step 1: Document** the user's enforced reenrolment, administrator identity-verification procedure, required database session driver, audit fields/retention, API/session invalidation and operational recovery limitations.
- [x] **Step 2: Run** `./planer dev docs:check`, `./planer dev format:check`, PHPStan, full isolated PHP tests, relevant Vitest tests and `git diff --check`; record unrelated baseline/build permission failures precisely. Full manual build succeeded; `docs:check` reports only the same 10 existing PHPDoc omissions in unchanged `User.php`.
- [ ] **Step 3: Senior Review Task 5 and whole recovery diff; fix findings and repeat affected checks.**
- [ ] **Step 4: Commit** documentation/review ledger as `docs(auth): 2FA-Wiederherstellung dokumentieren`.
- [ ] **Step 5: Add the implementation, per-task commits, review outcomes and verification to GitHub Issue #80, read the posted comment back, and keep the issue open for the Passkey and overall-review tasks.**
