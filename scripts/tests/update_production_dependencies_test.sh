#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$ROOT/scripts/update-production.sh"

grep -q 'git -C "\$ROOT" pull --ff-only' "$SCRIPT"
migration_line="$(grep -n 'php artisan migrate --force' "$SCRIPT" | cut -d: -f1)"

test -n "$migration_line"
test "$(sed -n "${migration_line}p" "$SCRIPT" | grep -c -- '--no-deps')" -eq 0

PROD_COMPOSE="$ROOT/compose.production.yaml"
soketi_start_line="$(grep -nF 'up -d --wait soketi' "$SCRIPT" | cut -d: -f1 || true)"
workers_start_line="$(grep -nF 'up -d --no-deps app horizon scheduler' "$SCRIPT" | cut -d: -f1 || true)"
test -n "$soketi_start_line"
test -n "$workers_start_line"
test "$soketi_start_line" -lt "$workers_start_line"
grep -Fq 'soketi: {condition: service_healthy}' "$PROD_COMPOSE"

echo 'Production update dependency tests passed'
