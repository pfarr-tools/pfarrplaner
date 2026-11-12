#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$ROOT/scripts/data-sync.sh"

grep -q "remote \"./planer exec -T mariadb" "$SCRIPT"
! grep -q "remote 'docker compose -f compose.yaml exec" "$SCRIPT"
grep -q 'docker compose -f compose.production.yaml run --rm --no-deps' "$SCRIPT"
grep -q -- '--use-aliases' "$SCRIPT"
! grep -q 'mariadb --force' "$SCRIPT"
grep -q 'PFARRPLANER_OBJECTS_BEGIN' "$SCRIPT"
grep -q 'base64 --decode' "$SCRIPT"
grep -q 'mc mirror --quiet' "$SCRIPT"
grep -q 'remote_planer up -d minio' "$SCRIPT"
grep -q '"\${COMPOSE\[@\]}" up -d --force-recreate minio' "$SCRIPT"
grep -q 'remote_planer artisan permission:cache-reset' "$SCRIPT"
grep -q 'exec -T app php artisan permission:cache-reset' "$SCRIPT"
awk '
  /^else$/ { in_pull = 1 }
  in_pull && /php artisan migrate --force/ { migrate = NR }
  in_pull && /remote_dump \| local_restore/ { restore = NR }
  END { exit !(migrate && restore && migrate < restore) }
' "$SCRIPT"

echo 'data sync tests passed'
