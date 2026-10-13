#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

test -f "$ROOT/backup-example.yaml"
test -f "$ROOT/docker/backup/Dockerfile"
test -f "$ROOT/docker/backup/backup.py"
test -x "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'profiles: \[tools\]' "$ROOT/compose.yaml"
grep -q 'profiles: \[tools\]' "$ROOT/compose.production.yaml"
grep -q 'docker/backup/Dockerfile' "$ROOT/compose.yaml"
grep -q 'docker/backup/Dockerfile' "$ROOT/compose.production.yaml"
grep -q '^/backup.yaml$' "$ROOT/.gitignore"
grep -q '^/secrets/$' "$ROOT/.gitignore"
grep -q '^backup.yaml$' "$ROOT/.dockerignore"
grep -q '^secrets$' "$ROOT/.dockerignore"
grep -q 'type: sftp' "$ROOT/backup-example.yaml"
grep -q 'require_offsite: false' "$ROOT/backup-example.yaml"
grep -q 'restic' "$ROOT/docker/backup/backup.py"
grep -q 'manifest.json' "$ROOT/docker/backup/backup.py"
grep -q 'mariadb-dump' "$ROOT/docker/backup/backup.py"
grep -q 'mc.*mirror' "$ROOT/docker/backup/backup.py"
! grep -q 'backup:run\|backup:monitor\|BACKUP_S3_BUCKET\|MINIO_BACKUP_BUCKET' "$ROOT/scripts/backup.sh" "$ROOT/compose.yaml" "$ROOT/compose.production.yaml"
! grep -q 'spatie/laravel-backup\|spatie/db-dumper' "$ROOT/src/composer.json" "$ROOT/src/composer.lock"
grep -q 'Backups sind nicht konfiguriert' "$ROOT/scripts/backup.sh"
bash -n "$ROOT/scripts/backup.sh"
python3 -m py_compile "$ROOT/docker/backup/backup.py"
git -C "$ROOT" diff --check

TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT
cp "$ROOT/.env.example" "$TEST_ROOT/.env.example"
PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" PLANER_BOOTSTRAP_BACKUPS=fixture "$ROOT/scripts/bootstrap.sh" --defaults >/dev/null
test "$(stat -c '%a' "$TEST_ROOT/backup.yaml")" = 600
grep -q '^  local:$' "$TEST_ROOT/backup.yaml"
grep -q '^  secondary:$' "$TEST_ROOT/backup.yaml"
! grep -q 'REPLACE_WITH\|REPLACE_THIS' "$TEST_ROOT/backup.yaml"

echo 'backup strategy tests passed'
