#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$ROOT/scripts/data-sync.sh"

grep -q "remote \"./planer exec -T mariadb" "$SCRIPT"
! grep -q "remote 'docker compose -f compose.yaml exec" "$SCRIPT"
grep -q 'docker compose -f compose.production.yaml run --rm --no-deps' "$SCRIPT"
! grep -q 'mariadb --force' "$SCRIPT"
awk '
  /^else$/ { in_pull = 1 }
  in_pull && /php artisan migrate --force/ { migrate = NR }
  in_pull && /remote_dump \| local_restore/ { restore = NR }
  END { exit !(migrate && restore && migrate < restore) }
' "$SCRIPT"

echo 'data sync tests passed'
