#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

grep -q '^name: \${COMPOSE_PROJECT_NAME:-pfarrplaner}$' "$ROOT/compose.yaml"
grep -q '^name: \${COMPOSE_PROJECT_NAME:-pfarrplaner}$' "$ROOT/compose.production.yaml"

echo 'Compose project name tests passed'
