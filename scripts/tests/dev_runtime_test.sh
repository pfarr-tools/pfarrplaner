#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
grep -q "listen = 9000" "$ROOT/Dockerfile"
grep -q "clear_env = no" "$ROOT/Dockerfile"
grep -q "catch_workers_output = yes" "$ROOT/Dockerfile"
grep -q "php8.4-posix" "$ROOT/Dockerfile"
grep -q 'php artisan config:clear' "$ROOT/compose.yaml"
grep -q 'chmod -R a+rwX storage bootstrap/cache /var/backups/pfarrplaner' "$ROOT/compose.yaml"
grep -q "env('CACHE_STORE', env('CACHE_DRIVER', 'file'))" "$ROOT/src/config/cache.php"
test -f "$ROOT/src/database/migrations/2014_10_12_200000_create_sessions_table.php"
grep -q -- "--requirepass" "$ROOT/compose.yaml"
grep -Fq 'redis-cli -a \"$$REDIS_PASSWORD\" ping' "$ROOT/compose.yaml"
grep -q -- '--max_allowed_packet=256M' "$ROOT/compose.yaml"
grep -q 'command: sh -lc "php artisan horizon"' "$ROOT/compose.yaml"
grep -q 'command: php artisan horizon' "$ROOT/compose.production.yaml"
grep -q '"laravel/horizon": "\^5"' "$ROOT/src/composer.json"
test -f "$ROOT/src/config/horizon.php"
echo 'Development runtime tests passed'
