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
- Schemaänderungen sind reversibel; Benutzer-IDs folgen dem vorhandenen `unsignedInteger`-Schema.
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

- [ ] **Step 1: Write failing migration/model tests** asserting the recovery marker column, audit fields/indexes, migration rollback, and `DemoBuilder` clears the marker and audit rows in demo data.
- Test names: `test_recovery_schema_and_audit_model_are_ready()` and `test_demo_builder_clears_recovery_markers_and_audits()`.
- [ ] **Step 2: Run the isolated tests and confirm they fail** with missing table/column behavior.
- [ ] **Step 3: Implement the reversible migration and documented model** with explicit fillable/casts/relations; extend demo cleanup.
- [ ] **Step 4: Run** `./planer dev test --no-browser --no-js`; expect all PHP tests to pass with only documented skips.
- [ ] **Step 5: Run Pint for the changed PHP files and `git diff --check`.**
- [ ] **Step 6: Senior Review Task 1; fix findings and repeat the relevant checks.**
- [ ] **Step 7: Commit** as `feat(auth): Audit für 2FA-Wiederherstellungen ergänzen`.

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
- Controller: `UserController::recoverTwoFactor(RecoverTwoFactorRequest $request, User $user): RedirectResponse`.
- Action: `RecoverUserTwoFactor::execute(User $actor, User $target, string $supportReference, string $ipAddress): TwoFactorRecoveryAudit`.
- Policy ability: `recoverTwoFactor(User $actor, User $target): bool`, true only for Super-Admin and when actor and target differ.
- Request accepts `current_password` and an ASCII support reference of 1–100 letters, digits, dots, underscores or hyphens; reject whitespace-only or free-form narratives.

- [ ] **Step 1: Write failing feature tests** `test_super_admin_can_recover_another_users_two_factor()`, `test_recovery_rejects_non_super_admin_and_self_recovery()`, `test_recovery_requires_active_or_pending_factor()`, `test_recovery_requires_current_admin_password_and_rate_limits_failures()`, `test_recovery_reloads_factor_after_admin_reauthentication()`, `test_recovery_consumes_pending_email_codes_and_writes_secret_free_audit()`, `test_recovery_requires_database_sessions()`, `test_recovery_revokes_database_sessions_and_personal_tokens()`, and `test_recovery_rolls_back_factor_changes_when_audit_write_fails()`.
- [ ] **Step 2: Run** `./planer dev test --no-browser --no-js`; confirm each new behavior fails for the intended reason.
- [ ] **Step 3: Implement policy and FormRequest**; check the actor password with `Hash::check`, apply a per-actor limit of five failures per minute before expensive work, and never log password input.
- [ ] **Step 4: Implement the Action** in one transaction: lock/reload target; require active or pending TOTP; clear both factor states with package-supported model methods; mark all unconsumed email challenges consumed; set `two_factor_recovery_required_at`; insert the audit row. Keep password and `must_change_password` unchanged.
- [ ] **Step 5: Reject the operation unless `config('session.driver') === 'database'`; revoke Sanctum tokens and target sessions using the configured session connection, and ensure failure is reported without claiming a successful recovery.**
- [ ] **Step 6: Re-run** `./planer dev test --no-browser --no-js`, Pint, PHPStan, and `git diff --check`.
- [ ] **Step 7: Senior Review Task 2; fix findings and repeat checks.**
- [ ] **Step 8: Commit** as `feat(auth): Super-Admins können verlorene 2FA widerrufen`.

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

- [ ] **Step 1: Write failing tests** `test_recovered_account_can_only_open_reenrolment_and_logout_routes()`, `test_recovered_account_cannot_call_authenticated_api_routes()`, `test_failed_or_incomplete_totp_confirmation_keeps_recovery_gate()`, and `test_successful_totp_confirmation_releases_recovery_gate()`; include guest, normal account, and simultaneous mandatory-password-change coverage.
- [ ] **Step 2: Run** `./planer dev test --no-browser --no-js` and confirm the gate tests fail.
- [ ] **Step 3: Implement middleware and route registration** in web/API middleware groups, ordered after authentication, with route-name allowlisting rather than broad URL-prefix exceptions.
- [ ] **Step 4: Clear the recovery marker only after successful TOTP confirmation** under the existing user-row lock; recovery failure must leave the account gated.
- [ ] **Step 5: Implement the dedicated page** with current-password entry, TOTP setup and confirmation, accessible errors, and no unrelated profile data or secret persistence after confirmation.
- [ ] **Step 6: Run PHP and focused Vue tests**, then Pint, Prettier, PHPStan, ESLint and `git diff --check`.
- [ ] **Step 7: Senior Review Task 3; fix findings and repeat checks.**
- [ ] **Step 8: Commit** as `feat(auth): 2FA-Wiederherstellung bis zur Neueinrichtung sperren`.

### Task 4: Super-Admin-Oberfläche und Audit-Historie

**Files:**
- Modify: `src/app/Http/Controllers/UserController.php` (user editor data)
- Modify: `src/resources/js/Pages/Admin/User/UserEditor.vue`
- Test: `src/tests/Feature/TwoFactorRecoveryFeatureTest.php`
- Test: `src/tests/js/Pages/Admin/User/UserEditor.test.js`

**Interfaces:**
- Only a Super-Admin editing another account sees the recovery action and audit history.
- Confirmation requires the Super-Admin's current password and support reference; copy states that prior identity verification is required and the target must reenroll TOTP.
- History shows timestamp, actor display name/ID, and support reference; it never shows factor material or authentication input.

- [ ] **Step 1: Write failing PHPUnit/Vitest tests** `test_only_super_admin_receives_recovery_action_and_history()`, `test_recovery_history_contains_actor_time_and_reference_only()`, and Vue cases for password/reference fields, cancel/submit, success/error feedback, and no secret rendering.
- [ ] **Step 2: Run the focused Vitest and isolated feature cases**; verify failures.
- [ ] **Step 3: Implement admin editor data and confirmation flow** using the Task 2 route; never include password or factor secrets in page props.
- [ ] **Step 4: Run** the full PHP suite and the relevant Vue tests; run Pint/Prettier and `git diff --check`.
- [ ] **Step 5: Senior Review Task 4; fix findings and repeat checks.**
- [ ] **Step 6: Commit** as `feat(auth): 2FA-Recovery in der Benutzerverwaltung anzeigen`.

### Task 5: Handbücher, Betriebsgrenze und Gesamtprüfung

**Files:**
- Modify: `docs/manual/benutzerhandbuch/einstellungen.md`
- Modify: `docs/manual/administratorhandbuch/sicherheit.md`
- Modify: `docs/manual/technikhandbuch/sicherheitsaudit.md`
- Modify: `docs/admin/installation-und-migration.md`
- Modify: `docs/superpowers/plans/2026-11-16-two-factor-recovery.md`
- Modify: `.superpowers/sdd/2026-11-15-passwortverwaltung/progress.md`

- [ ] **Step 1: Document** the user's enforced reenrolment, administrator identity-verification procedure, required database session driver, audit fields/retention, API/session invalidation and operational recovery limitations.
- [ ] **Step 2: Run** `./planer dev docs:check`, `./planer dev format:check`, PHPStan, full isolated PHP tests, relevant Vitest tests and `git diff --check`; record unrelated baseline/build permission failures precisely.
- [ ] **Step 3: Senior Review Task 5 and whole recovery diff; fix findings and repeat affected checks.**
- [ ] **Step 4: Commit** documentation/review ledger as `docs(auth): 2FA-Wiederherstellung dokumentieren`.
- [ ] **Step 5: Add the implementation, per-task commits, review outcomes and verification to GitHub Issue #80, read the posted comment back, and keep the issue open for the Passkey and overall-review tasks.**
