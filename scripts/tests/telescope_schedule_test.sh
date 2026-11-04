#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CONSOLE_ROUTES="$ROOT/src/routes/console.php"

grep -Fq "if (app()->environment('local') && class_exists(\\Laravel\\Telescope\\Telescope::class)) {" "$CONSOLE_ROUTES"
grep -Fq "    Schedule::command('telescope:prune --hours=48')->daily();" "$CONSOLE_ROUTES"

echo 'Telescope schedule tests passed'
