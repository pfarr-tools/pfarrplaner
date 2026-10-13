# Production-first Bootstrap Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make production the default Pfarrplaner runtime and extend the interactive production bootstrap to configure provider metadata, backups, mail/Postfix/DKIM, optional data import, and an initial administrator.

**Architecture:** Keep environment-specific Compose selection in the root `planer` dispatcher. Refactor bootstrap into focused shell helpers for core values, backups, mail, data import, and final validation; delegate database/user creation and existing restore/migration semantics to Laravel and the existing operational commands. The production wrapper automatically enables the Postfix profile only when requested by `.env`.

**Tech Stack:** Bash, Docker Compose, YAML, OpenSSL/OpenDKIM tooling, Laravel Artisan, existing Python Restic backup orchestrator.

**Spec:** `docs/superpowers/specs/2026-10-13-production-first-bootstrap-design.md`

## Global Constraints

- Production is selected by unqualified commands; development is selected only by `./planer dev ...`.
- `COMPOSE_PROJECT_NAME` is always `pfarrplaner`.
- `OCTANE_HTTPS=false` is the production default.
- `.env`, `backup.yaml`, private DKIM keys, passwords, backups, and runtime data must never be committed.
- Local legacy migration paths must not invoke SSH.
- Backup restore and legacy migration require explicit confirmation and must not print identity keys or passwords.
- Existing unrelated working-tree changes must be preserved.
- Every task ends with focused tests, `git diff --check`, and a focused commit using a German Conventional Commit message.

## Review Focus

- A production checkout must never silently use `compose.yaml`; Task 1 adds routing tests for every environment-sensitive command.
- A skipped backup setup must leave the normal production stack startable while backup commands fail with a specific configuration message; Task 3 tests both paths.
- A disabled Postfix setup must not leave App/Horizon pointing at nonexistent `postfix`; Task 4 tests external SMTP and Postfix profile resolution.
- A local legacy path must not be parsed as an SSH target; Task 5 tests local and remote path forms separately.
- A restored or migrated database must not receive a second bootstrap administrator; Task 5 tests that only the empty path creates the initial Super-Admin.

## File Map

- Modify `planer`: centralize production/development Compose dispatch, add `dev`, and make unqualified commands production-first.
- Modify `scripts/bootstrap.sh`: implement production/dev modes, provider/admin prompts, backup/mail/import orchestration, and final validation.
- Create `scripts/bootstrap-lib.sh` if helper size warrants it: reusable prompting, validation, secret generation, YAML-safe value handling, and cleanup traps.
- Modify `compose.production.yaml`: consume `.env` mail settings and keep Postfix optional without hard-coded application SMTP values.
- Modify `backup-example.yaml`: make it suitable as the generated production template without committed secrets or placeholder runtime configuration.
- Modify `scripts/backup.sh`: support bootstrap-driven initialization and a clear disabled-backup error.
- Modify `scripts/legacy-migrate.sh`: expose a noninteractive-safe dry-run/confirm interface for bootstrap orchestration if the current interface cannot be composed directly.
- Modify `src/app/Console/Commands/InstallCommand.php` or add a focused Artisan command: idempotently create the initial Super-Admin without rewriting `.env`.
- Modify `.env.example`: add bootstrap state such as `MAIL_POSTFIX_ENABLED` and ensure production mail settings have correct defaults.
- Modify `scripts/tests/bootstrap_test.sh`: cover production defaults, provider/admin setup, backup skip/multiple targets, and import branching.
- Create `scripts/tests/planer_routing_test.sh`: verify production-first and explicit development dispatch.
- Modify `scripts/tests/mail_strategy_test.sh` and `scripts/tests/backup_strategy_test.sh`: pin Compose/profile and generated configuration contracts.
- Modify `docs/admin/installation-und-migration.md`, `docs/mail.md`, and the three manuals: document the new bootstrap and command model.

### Task 1: Production-first command routing

**Files:**
- Modify: `planer`
- Create: `scripts/tests/planer_routing_test.sh`

**Interfaces:**
- `prod_compose()`: production Compose command, optionally adding `--profile mail` when `MAIL_POSTFIX_ENABLED=true`.
- `dev_compose()`: development Compose command.
- `./planer dev <command>`: explicit development namespace.

- [ ] **Step 1: Write failing routing tests** asserting `up`, `artisan`, `test`, `backup`, `manual`, and `migrate` resolve to production, while their `dev` forms resolve to development; assert `prod` remains an alias.
- [ ] **Step 2: Run `bash scripts/tests/planer_routing_test.sh` and verify failure against the current dispatcher.**
- [ ] **Step 3: Refactor `planer` around production-first and `dev_compose` dispatch without changing command-specific arguments. Move development-only helpers (`test`, `composer`, `npm`, `pint`, `fresh`) beneath `dev`.
- [ ] **Step 4: Add profile selection in the production wrapper based only on the validated `MAIL_POSTFIX_ENABLED` value; never enable the profile for development.**
- [ ] **Step 5: Run the routing test and existing shell/strategy tests; verify pass.**
- [ ] **Step 6: Run `git diff --check` and commit `refactor(cli): Produktionsbefehle zum Standard machen`.**

### Task 2: Core production bootstrap and initial administrator

**Files:**
- Modify: `scripts/bootstrap.sh`
- Modify: `.env.example`
- Modify: `src/app/Console/Commands/InstallCommand.php` or create `src/app/Console/Commands/InstallInitialAdmin.php`
- Modify: `scripts/tests/bootstrap_test.sh`

**Interfaces:**
- Bootstrap writes `.env` once and returns the collected admin data only to the one-shot initialization path.
- Artisan initial-admin command accepts explicit name/email/password input, hashes the password through the existing model/service, assigns `Super-Administrator:in`, and is idempotent by email.

- [ ] **Step 1: Add failing tests for `bootstrap --defaults`, `bootstrap --dev --defaults`, fixed `COMPOSE_PROJECT_NAME=pfarrplaner`, `APP_PROVIDER`, production defaults, and secret non-placeholder/non-empty values.**
- [ ] **Step 2: Add a focused Artisan test for creating one Super-Admin and for not overwriting an existing user on repeat execution.**
- [ ] **Step 3: Run the focused tests and verify the new assertions fail.**
- [ ] **Step 4: Refactor bootstrap mode parsing to make production the default and support `--dev`; remove the project-name prompt; add validated provider, administrator email, admin name, password, confirmation, and `must_change_password` collection.**
- [ ] **Step 5: Implement the idempotent initial-admin command by reusing `FirstRunInstallerService::createOrUpdateAdminUser` or extracting its safe operation into a dedicated service; never print the password.**
- [ ] **Step 6: Invoke the command only after the empty database has been migrated and seeded; skip it whenever backup restore or legacy migration was selected.**
- [ ] **Step 7: Run bootstrap and Laravel focused tests, `php -l` on changed PHP files, and `git diff --check`.**
- [ ] **Step 8: Commit `feat(bootstrap): Produktionsinstanzen mit Administrator einrichten`.**

### Task 3: Interactive backup target configuration

**Files:**
- Modify: `scripts/bootstrap.sh` or create `scripts/bootstrap-backup.sh`
- Modify: `backup-example.yaml`
- Modify: `scripts/backup.sh`
- Modify: `scripts/tests/bootstrap_test.sh`
- Modify: `scripts/tests/backup_strategy_test.sh`

**Interfaces:**
- `configure_backups()`: creates no file when skipped; otherwise writes `backup.yaml` mode `0600` with one or more repositories.
- `./planer backup init [--target NAME]`: remains the initialization primitive.

- [ ] **Step 1: Add fixture-driven tests for skip, one local target, multiple targets, SFTP target validation, generated unique passwords, and mode `0600`.**
- [ ] **Step 2: Run the tests and verify failure because bootstrap currently neither creates nor initializes `backup.yaml`.**
- [ ] **Step 3: Implement safe prompt helpers for repository name/type/required/path/URL and SFTP key paths; reject duplicate names, unsafe local paths, missing SFTP files, and weak permissions.**
- [ ] **Step 4: Generate YAML from structured values, inject current production DB/MinIO settings, use production Compose files, set `policy.require_offsite` from required offsite targets, and create local repository/staging directories.**
- [ ] **Step 5: Set `backup.yaml` to `0600`, invoke repository initialization through the production backup service, and distinguish required failures from optional warnings.**
- [ ] **Step 6: Make missing `backup.yaml` produce an explicit “Backups not configured” result while keeping `./planer up -d` independent of backups.**
- [ ] **Step 7: Run bootstrap/backup strategy tests and `git diff --check`.**
- [ ] **Step 8: Commit `feat(backup): Backup-Ziele im Bootstrap einrichten`.**

### Task 4: Mail, Postfix, and DKIM setup

**Files:**
- Modify: `scripts/bootstrap.sh` or create `scripts/bootstrap-mail.sh`
- Modify: `compose.production.yaml`
- Modify: `.env.example`
- Modify: `scripts/tests/mail_strategy_test.sh`
- Modify: `docs/mail.md`

**Interfaces:**
- `configure_mail()`: writes either disabled, external SMTP, or Postfix settings.
- `generate_dkim_key(domain, selector)`: creates private/public files under `secrets/dkim` without printing the private material.

- [ ] **Step 1: Add failing tests for the three mail modes, absence of hard-coded `postfix` application settings, Postfix profile activation, `MAIL_AUTO_TLS=false`, and DKIM path/permission contracts.**
- [ ] **Step 2: Run the mail strategy test and verify failure against the current hard-coded production environment.**
- [ ] **Step 3: Implement secret-safe external SMTP prompts, including hidden password input and validated host/port/TLS values.**
- [ ] **Step 4: Implement Postfix prompts for sender domain, hostname, direct/relay mode, relay credentials, selector, and DKIM generation using the writable key mount workaround.**
- [ ] **Step 5: Update production Compose so App/Horizon receive `.env` mail settings; keep Postfix in the `mail` profile and let the wrapper enable it only when requested.**
- [ ] **Step 6: Print only the public DKIM DNS record and final non-secret mail summary.**
- [ ] **Step 7: Run mail strategy tests, Compose config checks for both modes, and `git diff --check`.**
- [ ] **Step 8: Commit `feat(mail): Mailversand im Bootstrap konfigurieren`.**

### Task 5: Optional restore, legacy migration, and import branching

**Files:**
- Modify: `scripts/bootstrap.sh`
- Modify: `scripts/legacy-migrate.sh` only if orchestration needs a new safe entry point
- Modify: `scripts/backup.sh` only if restore needs a bootstrap-specific wrapper
- Modify: `scripts/tests/bootstrap_test.sh`
- Modify: `docs/admin/installation-und-migration.md`

**Interfaces:**
- `configure_initial_data()`: returns `empty`, `backup`, or `legacy` and performs the selected operation only after production services are healthy.
- Existing restore and migration commands remain the source of truth for data semantics and identity-key handling.

- [ ] **Step 1: Add tests using stubbed command executables to assert that `empty` creates an admin, `backup` invokes restore/apply with explicit confirmation, and `legacy` invokes a dry-run before confirmation.**
- [ ] **Step 2: Add tests proving `/absolute/local/path` is passed directly and `user@host:/absolute/path` is the only form invoking SSH.**
- [ ] **Step 3: Run the tests and verify failure because bootstrap currently has no import branch.**
- [ ] **Step 4: Start the production Compose stack from bootstrap only when an import or initial-admin operation requires it; wait for MariaDB, Redis, MinIO, and App health.**
- [ ] **Step 5: Implement backup selection and explicit apply confirmation through the existing safe restore path.**
- [ ] **Step 6: Implement legacy selection with local-path support, production mode, dry-run, explicit confirmation, and no secret output.**
- [ ] **Step 7: Ensure restore/migration branches skip initial-admin creation and finish with `optimize:clear`, health, and configuration checks.**
- [ ] **Step 8: Run focused import tests and `git diff --check`.**
- [ ] **Step 9: Commit `feat(bootstrap): Datenübernahme beim Produktionsstart anbieten`.**

### Task 6: Documentation, end-to-end bootstrap verification, and handoff

**Files:**
- Modify: `docs/admin/installation-und-migration.md`
- Modify: `docs/mail.md`
- Modify: `docs/manual/administratorhandbuch/*.md`
- Modify: `docs/manual/technikhandbuch/*.md`
- Modify: `scripts/tests/bootstrap_test.sh`

- [ ] **Step 1: Add an end-to-end shell fixture that runs production defaults with skipped import, asserts generated `.env`/secrets/configuration, and verifies the final suggested command is `./planer up -d`.**
- [ ] **Step 2: Update all operator documentation to use production-first commands and `./planer dev ...` for development.**
- [ ] **Step 3: Document backup target setup, Postfix/DKIM choices, optional restore/migration, initial-admin behavior, and the no-sudo `./planer` workflow.**
- [ ] **Step 4: Run the complete focused suite: `bash scripts/tests/planer_routing_test.sh`, `bash scripts/tests/bootstrap_test.sh`, `bash scripts/tests/backup_strategy_test.sh`, `bash scripts/tests/mail_strategy_test.sh`, all relevant PHP tests, and `git diff --check`.**
- [ ] **Step 5: Run `docker compose -f compose.production.yaml config` with representative external-SMTP and Postfix `.env` fixtures.**
- [ ] **Step 6: Perform a final review of `.gitignore`, staged paths, secret output, and production rollback behavior.**
- [ ] **Step 7: Commit `docs(ops): Produktions-Bootstrap dokumentieren`.**

## Final Verification

After all tasks:

```bash
bash scripts/tests/planer_routing_test.sh
bash scripts/tests/bootstrap_test.sh
bash scripts/tests/backup_strategy_test.sh
bash scripts/tests/mail_strategy_test.sh
git diff --check
git status --short
```

No `.env`, `backup.yaml`, secrets, backup data, vendor directories, or runtime
volumes may appear in the final staged diff.
