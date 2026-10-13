#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
IMPORT="$ROOT/scripts/bootstrap-import.sh"

test -x "$IMPORT"
grep -q 'pfarrplaner:initial-admin' "$IMPORT"
grep -q -- '--password-stdin' "$IMPORT"
grep -q -- '--prod --dry-run' "$IMPORT"
grep -q -- '--prod --confirm' "$IMPORT"
grep -q -- '--apply --yes' "$IMPORT"
grep -q 'PLANER_BOOTSTRAP_IMPORT_MODE' "$IMPORT"
grep -qi 'keine Daten' "$IMPORT"
grep -q "ui_choice 'Datenquelle wählen' 1 none backup legacy" "$IMPORT"

echo 'bootstrap import strategy tests passed'
