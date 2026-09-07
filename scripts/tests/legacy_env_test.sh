#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT

cat > "$TEST_ROOT/legacy.env" <<'EOF'
# Values are parsed as data, never sourced as shell code.
APP_KEY="base64:legacy-app-key"
DATABASE_KEY='base64:legacy-database-key'
DATABASE_CIPHER=AES-256-CBC
DB_DATABASE=legacy_db
DB_USERNAME=legacy_user
DB_PASSWORD="legacy password # not a comment"
MALICIOUS=$(touch "$TEST_ROOT/should-not-exist")
EOF

test "$(bash "$ROOT/scripts/legacy-env.sh" "$TEST_ROOT/legacy.env" APP_KEY)" = 'base64:legacy-app-key'
test "$(bash "$ROOT/scripts/legacy-env.sh" "$TEST_ROOT/legacy.env" DATABASE_KEY)" = 'base64:legacy-database-key'
test "$(bash "$ROOT/scripts/legacy-env.sh" "$TEST_ROOT/legacy.env" DB_PASSWORD)" = 'legacy password # not a comment'
test "$(bash "$ROOT/scripts/legacy-env.sh" "$TEST_ROOT/legacy.env" MALICIOUS)" = '$(touch "$TEST_ROOT/should-not-exist")'
test ! -e "$TEST_ROOT/should-not-exist"
grep -q 'docker ps --format' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'docker exec' "$ROOT/scripts/legacy-migrate.sh"
grep -q -- '--socket=/run/mysqld/mysqld.sock' "$ROOT/scripts/legacy-migrate.sh"
grep -q -- '--max_allowed_packet=256M' "$ROOT/scripts/legacy-migrate.sh"
grep -q -- '--ignore-table-data=' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'failed_jobs' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'telescope_entries' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'MARIADB_ROOT_PASSWORD' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'tar -C "\$legacy_files" -xf "\$archive"' "$ROOT/scripts/legacy-migrate.sh"
grep -q "remote 'test -d resources/bible'" "$ROOT/scripts/legacy-migrate.sh"
grep -q "remote 'tar -C resources -cf - bible'" "$ROOT/scripts/legacy-migrate.sh"
grep -q 'src/resources/bible' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'tar -C "\$ROOT/src/resources" -xf "\$legacy_bible_archive"' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'up -d --build --force-recreate app web horizon scheduler' "$ROOT/scripts/legacy-migrate.sh"
grep -q -- '--force-recreate app web horizon scheduler' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'php artisan demo:build' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'set_env_value DEMO_MODE true' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'set_env_value MAIL_HOST mailpit' "$ROOT/scripts/legacy-migrate.sh"
grep -q 'set_env_value MAIL_PORT 1025' "$ROOT/scripts/legacy-migrate.sh"
grep -q '^  mailpit:' "$ROOT/compose.production.yaml"

echo 'Legacy environment tests passed'
