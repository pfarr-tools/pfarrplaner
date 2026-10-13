#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT

mkdir -p "$TEST_ROOT/bin"
cat > "$TEST_ROOT/bin/docker" <<'EOF'
#!/usr/bin/env bash
printf '%s\n' "$*" > "${PLANER_ROUTING_LOG:?}"
EOF
chmod +x "$TEST_ROOT/bin/docker"

run_and_assert() {
  local expected_file="$1"
  shift
  : > "$PLANER_ROUTING_LOG"
  PATH="$TEST_ROOT/bin:$PATH" "$ROOT/planer" "$@" >/dev/null
  diff -u "$expected_file" "$PLANER_ROUTING_LOG"
}

printf 'compose -f %s/compose.production.yaml up -d\n' "$ROOT" > "$TEST_ROOT/production-up"
PLANER_ROUTING_LOG="$TEST_ROOT/log" run_and_assert "$TEST_ROOT/production-up" up -d

printf 'compose -f %s/compose.yaml up -d\n' "$ROOT" > "$TEST_ROOT/development-up"
PLANER_ROUTING_LOG="$TEST_ROOT/log" run_and_assert "$TEST_ROOT/development-up" dev up -d

printf 'compose -f %s/compose.production.yaml exec -e XDG_CONFIG_HOME=/tmp/config app php artisan about\n' "$ROOT" > "$TEST_ROOT/production-artisan"
PLANER_ROUTING_LOG="$TEST_ROOT/log" run_and_assert "$TEST_ROOT/production-artisan" artisan about

printf 'compose -f %s/compose.yaml exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e APP_KEY=base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI= app php artisan test\n' "$ROOT" > "$TEST_ROOT/development-test"
PLANER_ROUTING_LOG="$TEST_ROOT/log" run_and_assert "$TEST_ROOT/development-test" dev test

printf 'compose -f %s/compose.production.yaml ps\n' "$ROOT" > "$TEST_ROOT/production-alias"
PLANER_ROUTING_LOG="$TEST_ROOT/log" run_and_assert "$TEST_ROOT/production-alias" prod ps

echo 'planer routing tests passed'
