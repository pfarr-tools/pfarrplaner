#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HELPER="$ROOT/scripts/ensure-dkim-permissions.sh"
UPDATE="$ROOT/scripts/update-production.sh"
BOOTSTRAP="$ROOT/scripts/bootstrap-mail.sh"
test_root="$(mktemp -d)"
fake_bin="$test_root/bin"
dkim_dir="$test_root/secrets/dkim"
private_key="$dkim_dir/domain.private"
public_key="$dkim_dir/domain.txt"
docker_capture="$test_root/docker-args"

cleanup() {
  [[ -f "$docker_capture" ]] && unlink "$docker_capture"
  [[ -f "$test_root/.env" ]] && unlink "$test_root/.env"
  [[ -f "$private_key" ]] && unlink "$private_key"
  [[ -f "$public_key" ]] && unlink "$public_key"
  [[ -f "$fake_bin/docker" ]] && unlink "$fake_bin/docker"
  [[ -d "$dkim_dir" ]] && rmdir "$dkim_dir"
  [[ -d "$test_root/secrets" ]] && rmdir "$test_root/secrets"
  [[ -d "$fake_bin" ]] && rmdir "$fake_bin"
  rmdir "$test_root"
}
trap cleanup EXIT

[[ -x "$HELPER" ]]
mkdir -p "$fake_bin" "$dkim_dir"
printf 'MAIL_POSTFIX_ENABLED=true\n' > "$test_root/.env"
printf 'private test data\n' > "$private_key"
printf 'public test data\n' > "$public_key"
chmod 700 "$dkim_dir"
chmod 644 "$private_key"
chmod 600 "$public_key"
cat > "$fake_bin/docker" <<'DOCKER'
#!/usr/bin/env bash
printf '%s ' "$@" | tr '\n' ' ' > "$DKIM_DOCKER_CAPTURE"
DOCKER
chmod +x "$fake_bin/docker"

PATH="$fake_bin:$PATH" PLANER_ROOT="$test_root" DKIM_DOCKER_CAPTURE="$docker_capture" "$HELPER"
[[ "$(stat -c '%a' "$dkim_dir")" == 755 ]]
[[ "$(stat -c '%a' "$public_key")" == 644 ]]
grep -Fq -- '--profile mail run' "$docker_capture"
grep -Fq -- '--user 0 --entrypoint /bin/sh postfix' "$docker_capture"
grep -Fq 'chown opendkim:opendkim "$private_key"' "$docker_capture"
grep -Fq 'chmod 600 "$private_key"' "$docker_capture"
[[ "$(stat -c '%a' "$private_key")" == 644 ]]

grep -Fq 'ensure-dkim-permissions.sh' "$UPDATE"
grep -Fq 'PLANER_ROOT="$ROOT" "$SOURCE_ROOT/scripts/ensure-dkim-permissions.sh"' "$BOOTSTRAP"
! grep -Fq "find \"\$ROOT/secrets/dkim\" -type f -name '*.private' -exec chmod 600 {} +" "$UPDATE"
! grep -Fq 'chmod 600 "$ROOT/secrets/dkim/${domain}.private"' "$BOOTSTRAP"

echo 'DKIM permissions tests passed'
