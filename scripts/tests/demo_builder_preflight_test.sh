#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMMAND="$ROOT/src/app/Console/Commands/DevBuilder/DemoBuilder.php"

grep -q 'DEMO_MODE' "$COMMAND"
grep -q 'return self::FAILURE' "$COMMAND"

echo 'DemoBuilder preflight tests passed'
