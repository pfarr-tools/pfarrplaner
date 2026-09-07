#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE="$ROOT/compose.production.yaml"

app_block="$(sed -n '/^  app:/,/^  web:/p' "$COMPOSE")"
grep -Fq 'APP_URL' <<<"$app_block"
grep -Fq 'Host:' <<<"$app_block"
grep -Fq 'http://127.0.0.1:8000/api/health' <<<"$app_block"
! grep -Fq 'http://127.0.0.1:8000/up' <<<"$app_block"

echo 'Production app healthcheck tests passed'
