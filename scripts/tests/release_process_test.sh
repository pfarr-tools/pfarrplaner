#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

grep -q 'release)' "$ROOT/planer"
test -x "$ROOT/scripts/release.sh"
grep -q 'npm run release' "$ROOT/scripts/release.sh"
grep -q 'prod update --ref' "$ROOT/README.md"
grep -q 'prod update --ref' "$ROOT/docs/admin/installation-und-migration.md"

test ! -e "$ROOT/src/scripts/docker-build.js"
test ! -e "$ROOT/src/.woodpecker.yml"
test ! -e "$ROOT/src/Dockerfile"
test ! -e "$ROOT/src/compose.dev.yaml"
! grep -q 'docker:build' "$ROOT/src/package.json"
! grep -q 'docker-build.js' "$ROOT/src/scripts/release.js"
grep -q 'src/vendor' "$ROOT/.dockerignore"
grep -q 'backups' "$ROOT/.dockerignore"

echo 'Release process tests passed'
