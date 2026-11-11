#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LOCKFILE="$ROOT/src/composer.lock"

grep -q '"name": "peregrinus/bible-reference-parser"' "$LOCKFILE"
grep -q '"url": "https://github.com/pfarr-tools/bible-reference-parser"' "$LOCKFILE"
! grep -q '"url": "https://codeberg.org/peregrinus/bible-reference-parser"' "$LOCKFILE"

echo 'Bible reference parser migration tests passed'
