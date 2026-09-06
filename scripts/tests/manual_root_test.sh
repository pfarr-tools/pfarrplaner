#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

test -d "$ROOT/docs/manual/benutzerhandbuch"
test -d "$ROOT/docs/manual/administratorhandbuch"
test -d "$ROOT/docs/manual/technikhandbuch"
test ! -e "$ROOT/src/manual"
grep -q "PFARRPLANER_MANUAL_ROOT" "$ROOT/src/config/manual.php"
grep -q './docs/manual:/var/www/docs/manual' "$ROOT/compose.yaml"
grep -q "MANUAL_ROOT" "$ROOT/src/scripts/build-manual-site.js"
grep -q "config('manual.root'" "$ROOT/src/app/Console/Commands/DevBuilder/BuildManualPages.php"

echo 'Manual root tests passed'
