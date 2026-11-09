#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

grep -q 'if \[\[ -f "\$ROOT/src/\$file" \]\]; then' "$ROOT/planer"
grep -q 'process.env.QUALITY_FILES !== undefined' "$ROOT/src/scripts/changed-files.mjs"

echo 'Quality scope tests passed'
