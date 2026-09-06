#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE="$ROOT/compose.production.yaml"

redis_block="$(sed -n '/^  redis:/,/^  minio:/p' "$COMPOSE")"
grep -q 'REDIS_PASSWORD: ${REDIS_PASSWORD:?REDIS_PASSWORD muss gesetzt sein}' <<<"$redis_block"
grep -Fq '$$REDIS_PASSWORD' <<<"$redis_block"

echo 'Production Redis tests passed'
