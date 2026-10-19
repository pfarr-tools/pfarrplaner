#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

test -d "$ROOT/docs/manual/benutzerhandbuch"
test -d "$ROOT/docs/manual/administratorhandbuch"
test -d "$ROOT/docs/manual/technikhandbuch"
test -f "$ROOT/docs/manual/administratorhandbuch/mail.md"
test ! -e "$ROOT/src/manual"
grep -q "PFARRPLANER_MANUAL_ROOT" "$ROOT/src/config/manual.php"
grep -q './docs/manual:/var/www/docs/manual' "$ROOT/compose.yaml"
grep -q 'docker/manual/Dockerfile' "$ROOT/compose.yaml"
grep -q 'PFARRPLANER_MANUAL_ROOT' "$ROOT/compose.yaml"
grep -q "MANUAL_ROOT" "$ROOT/src/scripts/build-manual-site.js"
grep -q "config('manual.root'" "$ROOT/src/app/Console/Commands/DevBuilder/BuildManualPages.php"
grep -q './planer dev manual' "$ROOT/docs/manual/technikhandbuch/laufzeit-und-build.md"
! grep -R -q '../../mail.md' "$ROOT/docs/manual"
grep -q 'MinIO' "$ROOT/docs/manual/administratorhandbuch/installation-docker.md"
grep -q './planer update' "$ROOT/docs/manual/administratorhandbuch/updates.md"
grep -Fq 'if (!is_array($prefixes))' "$ROOT/src/app/Http/Middleware/HandleInertiaRequests.php"

echo 'Manual root tests passed'
