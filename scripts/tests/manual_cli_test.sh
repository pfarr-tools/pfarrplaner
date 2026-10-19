#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

help_output=$(./planer dev manual --help)
grep -Eq 'manual build|dusk|site|all|deploy|source-publish' <<<"$help_output"

if ./planer manual unknown >/tmp/pfarrplaner-manual-cli-test.out 2>&1; then
  echo 'Unbekannter Manual-Befehl wurde akzeptiert.' >&2
  exit 1
fi
grep -q './planer dev manual' /tmp/pfarrplaner-manual-cli-test.out

grep -q 'manual)' "$ROOT/planer"
grep -q 'manual.sh' "$ROOT/planer"
grep -q 'manual:build)' "$ROOT/planer"
grep -q 'manual:deploy)' "$ROOT/planer"
grep -q 'PLANER_MANUAL_DEV=1' "$ROOT/planer"
grep -q 'tests/Browser/Manual' "$ROOT/scripts/manual.sh"
grep -q 'manual_npm' "$ROOT/scripts/manual.sh"
grep -q 'PFARRPLANER_MANUAL_ROOT' "$ROOT/src/scripts/publish-manual-source.js"

echo 'Manual CLI tests passed'
