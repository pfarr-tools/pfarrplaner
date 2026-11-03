#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
VIEWS=(
  "$ROOT/src/resources/views/layouts/app.blade.php"
  "$ROOT/src/resources/views/whatis.blade.php"
  "$ROOT/src/resources/views/errors/503.blade.php"
)

for view in "${VIEWS[@]}"; do
  ! grep -q 'mix(' "$view"
  grep -Fq "@vite('resources/css/prebuild.css')" "$view"
done

grep -Fq "'resources/css/prebuild.css'" "$ROOT/src/vite.config.js"
echo 'Legacy asset views use the Vite CSS entry.'
