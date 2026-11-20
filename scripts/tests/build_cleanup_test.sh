#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cleanup="$ROOT/scripts/cleanup-build-artifacts.sh"
build="$ROOT/scripts/build-production.sh"
update="$ROOT/scripts/update-production.sh"

test -x "$cleanup"
grep -qF 'docker builder prune --all --force' "$cleanup"
grep -qF 'docker image prune --force' "$cleanup"
grep -qF "cleanup_repository 'pfarrplaner-app'" "$cleanup"
grep -qF 'cleanup_repository "$repository"' "$cleanup"
grep -qF 'cleanup-build-artifacts.sh' "$build"
grep -qF 'trap cleanup_after_build EXIT' "$build"

grep -qF 'cleanup-build-artifacts.sh' "$update"
grep -qF -- '--tag "$ref" --repository "$image_repository"' "$update"
health_line="$(grep -nF 'up -d --wait web' "$update" | cut -d: -f1)"
cleanup_line="$(grep -nF 'cleanup-build-artifacts.sh' "$update" | cut -d: -f1)"
test -n "$health_line"
test -n "$cleanup_line"
test "$cleanup_line" -gt "$health_line"

echo 'Build-Cleanup-Prüfungen erfolgreich.'
