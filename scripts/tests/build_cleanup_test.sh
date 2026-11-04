#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cleanup="$ROOT/scripts/cleanup-build-artifacts.sh"
build="$ROOT/scripts/build-production.sh"

test -x "$cleanup"
grep -qF 'docker builder prune --all --force' "$cleanup"
grep -qF 'docker image prune --force' "$cleanup"
grep -qF "cleanup_repository 'pfarrplaner-app'" "$cleanup"
grep -qF 'cleanup_repository "$repository"' "$cleanup"
grep -qF 'cleanup-build-artifacts.sh' "$build"
grep -qF 'trap cleanup_after_build EXIT' "$build"

echo 'Build-Cleanup-Prüfungen erfolgreich.'
