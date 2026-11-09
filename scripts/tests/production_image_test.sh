#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

grep -q 'image: \${PFARRPLANER_IMAGE:-pfarrtools/pfarrplaner:latest}' "$ROOT/compose.production.yaml"
! grep -q '^  build:' "$ROOT/compose.production.yaml"
! grep -q 'build-production.sh.*--push' "$ROOT/scripts/release.sh"
grep -q 'build-production.sh.*--push' "$ROOT/.github/workflows/release.yml"
grep -q 'build-production.sh' "$ROOT/planer"
grep -q 'docker build --pull --no-cache' "$ROOT/scripts/build-production.sh"
grep -q 'pull app' "$ROOT/scripts/update-production.sh"
grep -q -- '--build' "$ROOT/scripts/update-production.sh"

echo 'Production image tests passed'
