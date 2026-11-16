#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$ROOT/scripts/data-sync.sh"

assert_absent() {
  local pattern="$1"
  local file="$2"
  if grep -q "$pattern" "$file"; then
    echo "unexpected match in $file: $pattern" >&2
    exit 1
  fi
}

grep -q "remote \"./planer exec -T mariadb" "$SCRIPT"
assert_absent "remote 'docker compose -f compose.yaml exec" "$SCRIPT"
grep -q 'docker compose -f compose.production.yaml run --rm --no-deps' "$SCRIPT"
grep -q -- '--use-aliases' "$SCRIPT"
assert_absent 'mariadb --force' "$SCRIPT"
grep -q 'PFARRPLANER_OBJECTS_BEGIN' "$SCRIPT"
grep -q 'base64 --decode' "$SCRIPT"
grep -q 'mc mirror --quiet' "$SCRIPT"
grep -q 'remote_planer up -d minio' "$SCRIPT"
grep -q '"\${COMPOSE\[@\]}" up -d --force-recreate minio' "$SCRIPT"
grep -q 'remote_planer artisan permission:cache-reset' "$SCRIPT"
grep -q 'exec -T app php artisan permission:cache-reset' "$SCRIPT"
TEMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TEMP_DIR"' EXIT
MOCK_BIN="$TEMP_DIR/bin"
CALL_LOG="$TEMP_DIR/calls.log"
mkdir -p "$MOCK_BIN"
tar -cf "$TEMP_DIR/empty-objects.tar" --files-from /dev/null
OBJECTS_B64="$(base64 -w0 < "$TEMP_DIR/empty-objects.tar")"

cat > "$MOCK_BIN/docker" <<'MOCK_DOCKER'
#!/usr/bin/env bash
set -euo pipefail
printf 'docker %s\n' "$*" >> "$CALL_LOG"
if [[ "$*" == *'artisan tinker --execute='* ]]; then
  printf '%s\n' "${TARGET_CONFIG:-local|mysql|mariadb|pfarrplaner}"
elif [[ "$*" == *'exec -T mariadb printenv MARIADB_DATABASE'* ]]; then
  printf 'pfarrplaner\n'
fi
if [[ "$*" == *'artisan db:wipe --force'* && "${FAIL_WIPE:-0}" == 1 ]]; then
  exit 21
fi
if [[ "$*" == *'exec -T mariadb sh -lc mariadb '* ]]; then
  cat >/dev/null
  [[ "${FAIL_RESTORE:-0}" != 1 ]] || exit 22
elif [[ "$*" == *'create-buckets -lc'* ]]; then
  cat >/dev/null
fi
MOCK_DOCKER
chmod +x "$MOCK_BIN/docker"

cat > "$MOCK_BIN/ssh" <<'MOCK_SSH'
#!/usr/bin/env bash
set -euo pipefail
printf 'ssh %s\n' "$*" >> "$CALL_LOG"
if [[ "$*" == *'artisan down'* && "${FAIL_SOURCE_DOWN:-0}" == 1 ]]; then
  exit 24
fi
case "$*" in
  *mariadb-dump*)
    [[ "${FAIL_REMOTE_DUMP:-0}" != 1 ]] || exit 23
    printf 'TEST SQL DUMP\n'
    ;;
  *PFARRPLANER_OBJECTS_BEGIN*)
    printf 'PFARRPLANER_OBJECTS_BEGIN\n%s\nPFARRPLANER_OBJECTS_END\n' "$OBJECTS_B64"
    ;;
esac
MOCK_SSH
chmod +x "$MOCK_BIN/ssh"

export CALL_LOG OBJECTS_B64
export PATH="$MOCK_BIN:$PATH"

run_mock_pull() {
  : > "$CALL_LOG"
  PLANER_DEVELOPMENT=1 PLANER_PRODUCTION=0 "$SCRIPT" pull pfarrplaner@example.test:/opt/pfarrplaner
}

run_mock_pull
awk '
  /mariadb-dump/ { dump = NR }
  /php artisan down/ { down = NR }
  /php artisan db:wipe --force/ { wipe = NR }
  /exec -T mariadb sh -lc mariadb/ { restore = NR }
  /php artisan migrate --force/ { migrate = NR }
  /php artisan up/ { up = NR }
  END { exit !(dump && down && wipe && restore && migrate && up && dump < down && down < wipe && wipe < restore && restore < migrate && migrate < up) }
' "$CALL_LOG"

: > "$CALL_LOG"
if PLANER_DEVELOPMENT=1 PLANER_PRODUCTION=1 "$SCRIPT" pull pfarrplaner@example.test:/opt/pfarrplaner >/dev/null 2>&1; then
  echo 'conflicting compose modes unexpectedly succeeded' >&2
  exit 1
fi
[[ ! -s "$CALL_LOG" ]]

: > "$CALL_LOG"
if PLANER_DEVELOPMENT=1 PLANER_PRODUCTION=0 FAIL_REMOTE_DUMP=1 "$SCRIPT" pull pfarrplaner@example.test:/opt/pfarrplaner >/dev/null 2>&1; then
  echo 'remote dump failure unexpectedly succeeded' >&2
  exit 1
fi
assert_absent 'artisan db:wipe --force\|artisan down\|artisan up' "$CALL_LOG"

: > "$CALL_LOG"
if PLANER_DEVELOPMENT=1 PLANER_PRODUCTION=0 FAIL_SOURCE_DOWN=1 "$SCRIPT" pull pfarrplaner@example.test:/opt/pfarrplaner --source-down >/dev/null 2>&1; then
  echo 'source-down failure unexpectedly succeeded' >&2
  exit 1
fi
assert_absent 'artisan db:wipe --force\|exec -T app php artisan down' "$CALL_LOG"

: > "$CALL_LOG"
if PLANER_DEVELOPMENT=1 PLANER_PRODUCTION=0 FAIL_RESTORE=1 "$SCRIPT" pull pfarrplaner@example.test:/opt/pfarrplaner >/dev/null 2>&1; then
  echo 'local restore failure unexpectedly succeeded' >&2
  exit 1
fi
grep -q 'artisan db:wipe --force' "$CALL_LOG"
assert_absent 'artisan up' "$CALL_LOG"

: > "$CALL_LOG"
PLANER_PRODUCTION=1 PLANER_DEVELOPMENT=0 "$SCRIPT" pull pfarrplaner@example.test:/opt/pfarrplaner >/dev/null
awk '
  /php artisan migrate --force/ { migrate = NR }
  /mariadb-dump/ { dump = NR }
  /exec -T mariadb sh -lc mariadb/ { restore = NR }
  /php artisan db:wipe --force/ { wipe = NR }
  END { exit !(migrate && dump && restore && migrate < dump && migrate < restore && !wipe) }
' "$CALL_LOG"

: > "$CALL_LOG"
if PLANER_DEVELOPMENT=1 PLANER_PRODUCTION=0 TARGET_CONFIG=production\|mysql\|mariadb\|pfarrplaner "$SCRIPT" pull pfarrplaner@example.test:/opt/pfarrplaner >/dev/null 2>&1; then
  echo 'non-local target unexpectedly passed preflight' >&2
  exit 1
fi
assert_absent 'artisan down\|artisan db:wipe --force' "$CALL_LOG"

: > "$CALL_LOG"
if PLANER_DEVELOPMENT=1 PLANER_PRODUCTION=0 TARGET_CONFIG=local\|mysql\|remote-db\|pfarrplaner "$SCRIPT" pull pfarrplaner@example.test:/opt/pfarrplaner >/dev/null 2>&1; then
  echo 'external database target unexpectedly passed preflight' >&2
  exit 1
fi
assert_absent 'artisan down\|artisan db:wipe --force' "$CALL_LOG"

echo 'data sync tests passed'
