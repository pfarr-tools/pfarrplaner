#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
grep -q 'command: >-' "$ROOT/compose.production.yaml"
grep -q 'php artisan octane:start' "$ROOT/compose.production.yaml"
grep -q -- '--server=swoole' "$ROOT/compose.production.yaml"
grep -q 'reverse_proxy app:8000' "$ROOT/docker/caddy/Caddyfile.production"
grep -q 'exec php-fpm8.4 -F' "$ROOT/compose.yaml"
grep -q 'OCTANE_WORKERS' "$ROOT/.env.example"
echo 'Octane runtime tests passed'
