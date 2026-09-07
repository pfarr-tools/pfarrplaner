#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT
cp "$ROOT/.env.example" "$TEST_ROOT/.env.example"

PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --defaults >/dev/null
test -s "$TEST_ROOT/.env"
grep -q '^APP_KEY=base64:' "$TEST_ROOT/.env"
grep -q '^APP_ENV=local$' "$TEST_ROOT/.env"
grep -q '^APP_URL=http://localhost:8180$' "$TEST_ROOT/.env"
grep -q '^OCTANE_HTTPS=false$' "$TEST_ROOT/.env"
grep -q '^MAIL_DRIVER=smtp$' "$TEST_ROOT/.env"
grep -q '^APP_PORT=8180$' "$TEST_ROOT/.env"
grep -q '^VITE_PORT=5273$' "$TEST_ROOT/.env"
grep -B1 '^APP_URL=' "$TEST_ROOT/.env" | grep -q 'Public URL'

rm "$TEST_ROOT/.env"
PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --prod --defaults >/dev/null
grep -q '^APP_ENV=production$' "$TEST_ROOT/.env"
grep -q '^APP_DEBUG=false$' "$TEST_ROOT/.env"
grep -q '^APP_URL=https://localhost$' "$TEST_ROOT/.env"
grep -q '^OCTANE_HTTPS=true$' "$TEST_ROOT/.env"
grep -q '^MAIL_DRIVER=smtp$' "$TEST_ROOT/.env"
grep -B1 '^APP_URL=' "$TEST_ROOT/.env" | grep -q 'Public URL'

example_keys="$(grep -E '^[A-Z][A-Z0-9_]*=' "$TEST_ROOT/.env.example" | cut -d= -f1 | sort)"
generated_keys="$(grep -E '^[A-Z][A-Z0-9_]*=' "$TEST_ROOT/.env" | cut -d= -f1 | sort)"
test "$example_keys" = "$generated_keys"
while IFS= read -r key; do
  grep -B1 "^${key}=" "$TEST_ROOT/.env" | head -1 | grep -q '^# '
done <<<"$generated_keys"

printf 'APP_KEY=existing\n' > "$TEST_ROOT/.env"
PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --defaults >/dev/null
grep -qx 'APP_KEY=existing' "$TEST_ROOT/.env"

echo 'bootstrap tests passed'
