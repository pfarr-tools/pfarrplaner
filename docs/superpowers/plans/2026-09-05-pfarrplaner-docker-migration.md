# Pfarrplaner Docker- und Migrationsarchitektur Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a safe MariaDB/MinIO Docker runtime for Pfarrplaner with `planer`, Soketi, optional Postfix, backups, synchronization, and legacy SSH migration.

**Architecture:** Keep Laravel under `src/`, add root-level operational files, and use separate development and production Compose projects. Retain Spatie Backup for database archives while adding a coordinated MinIO object backup and migration layer.

**Tech Stack:** Docker Compose, PHP-FPM/Laravel 11, MariaDB 11, Redis 7, MinIO, Soketi, Caddy, Postfix, Spatie Laravel Backup, Bash, `mariadb-dump`/`mariadb`.

**Spec:** `docs/superpowers/specs/2026-09-05-pfarrplaner-docker-migration-design.md`

## Global Constraints

- MariaDB remains the production database and logical dumps are used for portability.
- MinIO/S3 object storage is mandatory for durable application files.
- `./planer test` must never target the normal MariaDB database.
- Mail is optional through a Compose profile and send-only.
- Never overwrite `.env`; never commit or push without explicit user request.
- All operational and migration behavior must be documented in the dedicated install/migration manual.

### Task 1: Root structure and Compose services

**Files:** Create root `planer`, `compose.yaml`, `compose.production.yaml`, `.env.example`, `Dockerfile`, `docker/` service configurations; adapt `src/config/filesystems.php`, `src/config/broadcasting.php`, `src/config/backup.php`.

- [ ] Add the root wrapper and development services for app, web, vite, horizon, scheduler, MariaDB, Redis, MinIO, bucket initialization, Soketi, and Mailpit.
- [ ] Add the production services with internal backend networking, persistent volumes, health checks, and optional `mail` profile.
- [ ] Build PHP images with `pdo_mysql`, MariaDB dump client, storage prerequisites, and Node tooling.
- [ ] Configure Laravel S3 storage against MinIO and Soketi against the internal broadcast service while retaining local temporary storage.
- [ ] Validate Compose interpolation and service health with `docker compose config`.

### Task 2: Safe `planer` operations and tests

**Files:** Modify root `planer`; create `scripts/planer-test.sh` and shell tests under `tests/Infrastructure/` or `scripts/tests/`.

- [ ] Implement `up`, `down`, `restart`, `status`, `logs`, `shell`, `artisan`, `composer`, `npm`, `pint`, `test`, `fresh`, and production subcommands.
- [ ] Block `artisan test` without exact interactive `ja`, and force testing environment variables for `planer test`.
- [ ] Block unapproved destructive database commands and check production environment before production operations.
- [ ] Add tests for denial on EOF/non-interactive input, testing database isolation, and required production confirmation.
- [ ] Run shell tests and a focused Laravel smoke test.

### Task 3: Spatie Backup and object backup coordination

**Files:** Modify `src/config/backup.php`, `src/config/filesystems.php`, `src/app/Listeners/PrepareBackup.php`; create `scripts/backup.sh`, object-backup helper, and backup tests.

- [ ] Preserve existing Spatie destinations, encryption, retention, notifications, and health checks while setting archive verification on.
- [ ] Exclude transient framework state and ensure the application bucket cannot be the backup destination.
- [ ] Implement a stable backup ID, MinIO object mirror, manifest, checksums, and independent backup destination.
- [ ] Add `planer backup create|list|verify|restore` with explicit restore confirmation and dry-run.
- [ ] Make failed-job/Telescope cleanup explicit, safe, and documented rather than an invisible backup side effect.
- [ ] Verify a backup in an isolated Compose project and test a representative object restore.

### Task 4: MariaDB/MinIO data push and pull

**Files:** Create `scripts/data-sync.sh`; add wrapper dispatch and synchronization tests.

- [ ] Implement SSH path validation and source/target maintenance-mode cleanup traps.
- [ ] Dump and restore MariaDB with `mariadb-dump`/`mariadb`, dropping only the target application schema after confirmation.
- [ ] Transfer all MinIO buckets through a temporary client container while preserving keys and avoiding backup buckets.
- [ ] Run target migrations, clear caches, and verify database/object counts before returning services online.
- [ ] Test push/pull with two local Compose projects and injected failures to prove maintenance recovery.

### Task 5: Legacy file/MySQL migration

**Files:** Create `scripts/legacy-migrate.sh`, migration validation helper, and fixture/test data; modify root `planer`.

- [ ] Add read-only preflight for SSH access, legacy paths, MySQL credentials, disk space, and destination health.
- [ ] Create timestamped immutable legacy SQL and file archives before cutover.
- [ ] Discover and transfer `storage/app`, public files, inbox files, and other configured legacy roots into MinIO without changing database keys.
- [ ] Import the MariaDB dump, run migrations, and validate referenced objects, sizes, MIME types, and checksums.
- [ ] Provide dry-run, retry-safe staging, cutover, rollback, and post-cutover verification commands.

### Task 6: Dedicated installation/migration manual

**Files:** Create `docs/admin/installation-und-migration.md`; modify `docs/admin/README.md`, root `README.md`, `src/INSTALL.md`, and technical manual indexes.

- [ ] Document prerequisites, layout, environment variables, development, production, Soketi, MinIO, backups, restores, data push/pull, and legacy migration.
- [ ] Document optional Postfix relay/direct delivery, DKIM, SPF, DMARC, DNS, rDNS, secrets, firewall, and failure handling.
- [ ] Add exact command examples, preflight/checklists, retention policy, restore drills, and rollback procedures.
- [ ] Build or lint Markdown/manual artifacts if the repository provides a manual build command.

### Task 7: Verification and handoff

**Files:** No new production files; inspect all changed files.

- [ ] Run Compose config checks for both environments.
- [ ] Run focused Laravel tests, shell tests, frontend build, Pint, and `git diff --check`.
- [ ] Perform a live smoke test covering login, upload/download, broadcasting, queue, scheduled task, backup, and restore.
- [ ] Report any unavailable Docker/network/SMTP verification separately from passing checks.

