#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CONSOLE_ROUTES="$ROOT/src/routes/console.php"

grep -Fq "if (app()->environment('local') && class_exists(Telescope::class)) {" "$CONSOLE_ROUTES"
grep -Fq "    Schedule::command('telescope:prune --hours=48')->daily();" "$CONSOLE_ROUTES"
grep -Fq "Schedule::command('youtube:update')" "$CONSOLE_ROUTES"
grep -Fq "    ->everyTenMinutes()" "$CONSOLE_ROUTES"
grep -Fq "Schedule::command('youtube:unpublish')" "$CONSOLE_ROUTES"
grep -Fq "Schedule::command('query:clear')" "$CONSOLE_ROUTES"
grep -Fq "    ->dailyAt('03:00')" "$CONSOLE_ROUTES"
grep -Fq "Schedule::command('liturgy:get')" "$CONSOLE_ROUTES"
grep -Fq "    ->dailyAt('05:01')" "$CONSOLE_ROUTES"
grep -Fq "Schedule::command('registrations:expire')" "$CONSOLE_ROUTES"
grep -Fq "    ->dailyAt('05:10')" "$CONSOLE_ROUTES"

echo 'Application schedule tests passed'
