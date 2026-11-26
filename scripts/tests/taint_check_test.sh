#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

test -f "$ROOT/src/psalm.xml"
grep -q 'taintAnalysis="true"' "$ROOT/src/psalm.xml"
grep -q '<directory name="app" />' "$ROOT/src/psalm.xml"
grep -q '<directory name="routes" />' "$ROOT/src/psalm.xml"
grep -q '<directory name="database" />' "$ROOT/src/psalm.xml"
grep -q '"vimeo/psalm"' "$ROOT/src/composer.json"
grep -q 'taint:check' "$ROOT/src/composer.json"
grep -q 'taint:check' "$ROOT/planer"
grep -q 'run_dev taint:check' "$ROOT/planer"
grep -q 'Psalm-Taint-Analyse' "$ROOT/docs/manual/technikhandbuch/codequalitaet-und-workflow.md"
! grep -q '\$_GET' "$ROOT/src/app/Services/AmazonService.php"

echo 'Taint check tests passed'
