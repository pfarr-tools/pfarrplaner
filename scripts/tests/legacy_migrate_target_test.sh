#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$ROOT/scripts/legacy-migrate.sh"

grep -q -- '\[--dev|--prod|--demo\]' "$SCRIPT"
grep -q -- '--demo' "$SCRIPT"
grep -q 'target="\${target:-dev}"' "$SCRIPT"
grep -q -- '--dev)' "$SCRIPT"
grep -q -- '--prod)' "$SCRIPT"
grep -q -- '--demo)' "$SCRIPT"
grep -q 'compose.production.yaml' "$SCRIPT"
grep -q 'APP_ENV=production' "$SCRIPT"

output="$(bash "$SCRIPT" example:relative --dry-run 2>&1 || true)"
grep -q 'Zielumgebung: dev' <<<"$output"

output="$(bash "$SCRIPT" example:/srv/legacy --prod --dry-run 2>&1 || true)"
grep -q 'Für --prod und --demo muss APP_ENV=production gesetzt sein' <<<"$output"

output="$(bash "$SCRIPT" example:/srv/legacy --dry-run --dev --prod 2>&1 || true)"
grep -q 'nicht gleichzeitig' <<<"$output"

echo 'legacy migration target tests passed'
