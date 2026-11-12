#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

test -f "$ROOT/backup-example.yaml"
test -f "$ROOT/docker/backup/Dockerfile"
test -f "$ROOT/docker/backup/backup.py"
test -x "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'bootstrap-ui.sh' "$ROOT/scripts/bootstrap-backup.sh"
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
grep -q 'run --build --rm --no-deps backup' "$ROOT/scripts/backup.sh"
grep -q 'php artisan down' "$ROOT/scripts/backup.sh"
grep -q 'php artisan up' "$ROOT/scripts/backup.sh"
grep -q 'isDownForMaintenance' "$ROOT/scripts/backup.sh"
! grep -q 'storage/framework/down' "$ROOT/scripts/backup.sh"
grep -q 'maintenance_set' "$ROOT/scripts/backup.sh"
grep -q 'quiesce_backup_workers' "$ROOT/scripts/backup.sh"
grep -q 'for service in horizon scheduler' "$ROOT/scripts/backup.sh"
! grep -q 'quiesce$' "$ROOT/scripts/backup.sh"
! grep -q 'quiesce.*app' "$ROOT/scripts/backup.sh"
grep -q 'trap.*restore' "$ROOT/scripts/backup.sh"
grep -q 'php artisan permission:cache-reset' "$ROOT/scripts/backup.sh"
grep -q 'Wartungsstatus konnte nicht ermittelt werden' "$ROOT/scripts/backup.sh"
grep -q 'Wartungsmodus konnte nicht aufgehoben werden' "$ROOT/scripts/backup.sh"
python3 - "$ROOT/scripts/backup.sh" <<'PY'
from pathlib import Path
import sys

source = Path(sys.argv[1]).read_text()
workers = source.split("quiesce_backup_workers()", 1)[1].split("app_in_maintenance()", 1)[0]
assert "for service in horizon scheduler" in workers
assert "app" not in workers
create = source.split("  create)", 1)[1].split("  restore)", 1)[0]
assert create.index("enter_backup_maintenance") < create.index("quiesce_backup_workers")
assert create.index("leave_backup_maintenance") < create.index("touch \"$release_file\"")
assert "kill \"$backup_pid\"" in source
PY
grep -q 'networks: \[backend, mail-egress\]' "$ROOT/compose.production.yaml"
grep -q 'sftp:BENUTZER@HOST:/absoluter/remote/pfad' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'SFTP-Passwort' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'ssh_password_file' "$ROOT/scripts/bootstrap-backup.sh"
! grep -q 'sshpass' "$ROOT/docker/backup/Dockerfile"
! grep -q 'sshpass' "$ROOT/docker/backup/backup.py"
grep -q 'SSH_ASKPASS_REQUIRE=force' "$ROOT/docker/backup/backup.py"
grep -q 'restic-ssh-askpass' "$ROOT/docker/backup/backup.py"
grep -q 'restic-ssh-wrapper' "$ROOT/docker/backup/backup.py"
grep -q 'sftp.command={ssh_wrapper}' "$ROOT/docker/backup/backup.py"
! grep -q 'sftp.command=env' "$ROOT/docker/backup/backup.py"
grep -q 'paramiko' "$ROOT/docker/backup/Dockerfile"
grep -q 'repository_exists' "$ROOT/docker/backup/backup.py"
grep -q '\[j/N\]' "$ROOT/docker/backup/backup.py"
grep -q '"j", "ja", "y", "yes"' "$ROOT/docker/backup/backup.py"
grep -q 'replace_repository' "$ROOT/docker/backup/backup.py"
! grep -q 'run --build --rm --no-deps -T backup' "$ROOT/scripts/backup.sh"
grep -q 'sftp.command' "$ROOT/docker/backup/backup.py"
grep -q 'ssh_password_file' "$ROOT/docker/backup/backup.py"
grep -q 'ConnectTimeout=15' "$ROOT/docker/backup/backup.py"
grep -q 'NumberOfPasswordPrompts=1' "$ROOT/docker/backup/backup.py"
grep -q 'ssh_destination' "$ROOT/docker/backup/backup.py"
grep -q -- '-s {ssh_destination} sftp' "$ROOT/docker/backup/backup.py"
grep -q 'ssh_port' "$ROOT/docker/backup/backup.py"
! grep -q '{ssh_target} -s sftp' "$ROOT/docker/backup/backup.py"
grep -q 'initialisiere...' "$ROOT/docker/backup/backup.py"
grep -q 'ssh-keyscan' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'ssh-keygen' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'Host-Schlüssel übernehmen' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'ui_confirm' "$ROOT/scripts/bootstrap-ui.sh"
grep -q 'ui_confirm.*yes' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'ui_confirm.*no' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'local_count' "$ROOT/scripts/bootstrap-backup.sh"
grep -q 'remote_count' "$ROOT/scripts/bootstrap-backup.sh"
! grep -q 'Backup-Ziel .* Name:' "$ROOT/scripts/bootstrap-backup.sh"
grep -q "ui_choice 'Typ wählen' 1 local sftp" "$ROOT/scripts/bootstrap-backup.sh"
grep -q "ui_choice 'SSH-Authentifizierung wählen' 1 key password" "$ROOT/scripts/bootstrap-backup.sh"
grep -q "ui_confirm 'Backups einrichten?' yes" "$ROOT/scripts/bootstrap-backup.sh"
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
