#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$ROOT/scripts/update-production.sh"

migration_line="$(grep -n 'php artisan migrate --force' "$SCRIPT" | cut -d: -f1)"

test -n "$migration_line"
test "$(sed -n "${migration_line}p" "$SCRIPT" | grep -c -- '--no-deps')" -eq 0

echo 'Production update dependency tests passed'
