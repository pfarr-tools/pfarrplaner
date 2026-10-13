#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

test -f "$ROOT/backup-example.yaml"
test -f "$ROOT/docker/backup/Dockerfile"
test -f "$ROOT/docker/backup/backup.py"
test -x "$ROOT/scripts/bootstrap-backup.sh"
python3 - "$ROOT/scripts/bootstrap-backup.sh" <<'PY'
from pathlib import Path
import re
import sys

source = Path(sys.argv[1]).read_text()
assert not re.search(r'repository_yaml+="[^"]*\\n', source)
assert '((count += 1))' in source
PY
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
grep -q 'required_failures' "$ROOT/docker/backup/backup.py"
grep -q 'Initialisierung fehlgeschlagen' "$ROOT/docker/backup/backup.py"
grep -q 'manifest.json' "$ROOT/docker/backup/backup.py"
grep -q 'mariadb-dump' "$ROOT/docker/backup/backup.py"
grep -q 'mc.*mirror' "$ROOT/docker/backup/backup.py"
! grep -q 'backup:run\|backup:monitor\|BACKUP_S3_BUCKET\|MINIO_BACKUP_BUCKET' "$ROOT/scripts/backup.sh" "$ROOT/compose.yaml" "$ROOT/compose.production.yaml"
! grep -q 'spatie/laravel-backup\|spatie/db-dumper' "$ROOT/src/composer.json" "$ROOT/src/composer.lock"
grep -q 'Backups sind nicht konfiguriert' "$ROOT/scripts/backup.sh"
grep -q 'run --build --rm --no-deps -T backup' "$ROOT/scripts/backup.sh"
grep -q 'networks: \[backend, mail-egress\]' "$ROOT/compose.production.yaml"
grep -q 'sftp:BENUTZER@HOST:/absoluter/remote/pfad' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'SFTP-Passwort' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'ssh_password_file' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'sshpass' "$ROOT/docker/backup/Dockerfile"
grep -q 'sshpass' "$ROOT/docker/backup/backup.py"
grep -q 'ssh_wrapper' "$ROOT/docker/backup/backup.py"
grep -q 'ssh_password_file' "$ROOT/docker/backup/backup.py"
grep -q 'ConnectTimeout=15' "$ROOT/docker/backup/backup.py"
grep -q 'NumberOfPasswordPrompts=1' "$ROOT/docker/backup/backup.py"
grep -q 'initialisiere...' "$ROOT/docker/backup/backup.py"
grep -q 'ssh-keyscan' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'ssh-keygen' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'Host-Schlüssel übernehmen' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'local_count' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'remote_count' "$ROOT/scripts/bootstrap-backup.sh"
! grep -q 'Backup-Ziel .* Name:' "$ROOT/scripts/bootstrap-backup.sh"
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
