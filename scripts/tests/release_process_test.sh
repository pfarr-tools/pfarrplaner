#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

grep -q 'release)' "$ROOT/planer"
test -x "$ROOT/scripts/release.sh"
grep -q 'npm run release' "$ROOT/scripts/release.sh"
! grep -q 'build-production.sh.*--push' "$ROOT/scripts/release.sh"
grep -q 'cleanup-build-artifacts.sh' "$ROOT/scripts/build-production.sh"
grep -q 'git -C "\$ROOT" push origin main "\$tag"' "$ROOT/scripts/release.sh"
! grep -q "\['dev', 'manual', 'all'\]" "$ROOT/src/scripts/release.js"
grep -q "'src/CHANGELOG.md'" "$ROOT/src/scripts/release.js"
grep -q "'src/package.json'" "$ROOT/src/scripts/release.js"
grep -q "'src/package-lock.json'" "$ROOT/src/scripts/release.js"
WORKFLOW="$ROOT/.github/workflows/release.yml"
test -f "$WORKFLOW"
grep -q "tags:" "$WORKFLOW"
grep -q '^  workflow_dispatch:$' "$WORKFLOW"
test "$(grep -Fc "if: github.event_name == 'push'" "$WORKFLOW")" -eq 4
grep -q 'docker/build-push-action' "$WORKFLOW"
grep -Fq 'tags: ${{ env.IMAGE_REPOSITORY }}:${{ github.ref_name }}' "$WORKFLOW"
grep -q 'manual all' "$WORKFLOW"
grep -q 'manual deploy' "$WORKFLOW"
grep -q 'DOCKERHUB_TOKEN' "$WORKFLOW"
grep -q 'MANUAL_DEPLOY_SSH_KEY' "$WORKFLOW"
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
