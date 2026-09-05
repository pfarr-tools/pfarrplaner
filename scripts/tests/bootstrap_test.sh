#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT
cp "$ROOT/.env.example" "$TEST_ROOT/.env.example"

PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --defaults >/dev/null
test -s "$TEST_ROOT/.env"
grep -q '^APP_KEY=base64:' "$TEST_ROOT/.env"
grep -q '^APP_PORT=8180$' "$TEST_ROOT/.env"
grep -q '^VITE_PORT=5273$' "$TEST_ROOT/.env"

printf 'APP_KEY=existing\n' > "$TEST_ROOT/.env"
PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --defaults >/dev/null
grep -qx 'APP_KEY=existing' "$TEST_ROOT/.env"

echo 'bootstrap tests passed'
