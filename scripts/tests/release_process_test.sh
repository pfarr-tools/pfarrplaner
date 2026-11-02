#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

grep -q 'release)' "$ROOT/planer"
test -x "$ROOT/scripts/release.sh"
grep -q 'npm run release' "$ROOT/scripts/release.sh"
grep -q 'build-production.sh.*--push' "$ROOT/scripts/release.sh"
grep -q 'git -C "\$ROOT" push origin main "\$tag"' "$ROOT/scripts/release.sh"
grep -q "\['dev', 'down', '--remove-orphans'\]" "$ROOT/src/scripts/release.js"
grep -q "\['dev', 'up', '-d', '--remove-orphans'\]" "$ROOT/src/scripts/release.js"
grep -q "'compose', '-f', composeFile, 'config', '--format', 'json'" "$ROOT/src/scripts/release.js"
grep -q "'network', 'inspect', networkName" "$ROOT/src/scripts/release.js"
grep -q "'network', 'disconnect', '-f', networkName, container.Name" "$ROOT/src/scripts/release.js"
grep -q "\['dev', 'manual', 'all'\]" "$ROOT/src/scripts/release.js"
grep -q 'src/resources' "$ROOT/src/scripts/release.js"
grep -q 'src/public' "$ROOT/src/scripts/release.js"
grep -q -- '--skip-dusk' "$ROOT/src/scripts/release.js"
grep -q "'src/CHANGELOG.md'" "$ROOT/src/scripts/release.js"
grep -q "'src/package.json'" "$ROOT/src/scripts/release.js"
grep -q "'src/package-lock.json'" "$ROOT/src/scripts/release.js"
grep -q 'pfarrtools/pfarrplaner:vVERSION' "$ROOT/docs/admin/installation-und-migration.md"
grep -q 'pfarrtools/pfarrplaner:vVERSION' "$ROOT/docs/manual/technikhandbuch/deployment.md"

test ! -e "$ROOT/src/scripts/docker-build.js"
test ! -e "$ROOT/src/.woodpecker.yml"
test ! -e "$ROOT/src/Dockerfile"
test ! -e "$ROOT/src/compose.dev.yaml"
! grep -q 'docker:build' "$ROOT/src/package.json"
! grep -q 'docker-build.js' "$ROOT/src/scripts/release.js"
grep -q 'src/vendor' "$ROOT/.dockerignore"
grep -q 'backups' "$ROOT/.dockerignore"

echo 'Release process tests passed'
