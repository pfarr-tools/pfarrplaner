#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DOCKERFILE="$ROOT/Dockerfile"

grep -q 'storage/framework/cache storage/framework/sessions storage/framework/views' "$DOCKERFILE"
! grep -q 'storage/framework/{cache,sessions,views}' "$DOCKERFILE"

echo 'Dockerfile runtime tests passed'
