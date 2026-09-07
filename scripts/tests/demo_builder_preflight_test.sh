#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMMAND="$ROOT/src/app/Console/Commands/DevBuilder/DemoBuilder.php"

grep -q 'DEMO_MODE' "$COMMAND"
grep -q 'return self::FAILURE' "$COMMAND"
prep_users="$(sed -n '/protected function prepUsers()/,/protected function handleUsers()/p' "$COMMAND")"
if grep -q -- '->change()' <<<"$prep_users"; then
  echo 'prepUsers must not alter the api_token column' >&2
  exit 1
fi

echo 'DemoBuilder preflight tests passed'
