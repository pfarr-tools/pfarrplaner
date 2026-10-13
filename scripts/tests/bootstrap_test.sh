#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
test -f "$ROOT/scripts/bootstrap-ui.sh"
grep -q 'NO_COLOR' "$ROOT/scripts/bootstrap-ui.sh"
grep -q 'ui_text' "$ROOT/scripts/bootstrap-ui.sh"
grep -q 'ui_choice' "$ROOT/scripts/bootstrap-ui.sh"
! grep -q 'read -e -r -i' "$ROOT/scripts/bootstrap.sh" "$ROOT/scripts/bootstrap-mail.sh" "$ROOT/scripts/bootstrap-import.sh"
grep -q 'ui_section' "$ROOT/scripts/bootstrap.sh"
grep -q 'ui_success' "$ROOT/scripts/bootstrap.sh"
TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT
cp "$ROOT/.env.example" "$TEST_ROOT/.env.example"

for key in DB_PASSWORD DB_ROOT_PASSWORD REDIS_PASSWORD AWS_SECRET_ACCESS_KEY PUSHER_APP_KEY PUSHER_APP_SECRET; do
  grep -q "^${key}=$" "$TEST_ROOT/.env.example"
done
! grep -Eq '=(change-|REPLACE_THIS_WITH)' "$TEST_ROOT/.env.example"

PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --defaults >/dev/null
test -s "$TEST_ROOT/.env"
grep -q '^APP_KEY=base64:' "$TEST_ROOT/.env"
grep -q '^APP_ENV=production$' "$TEST_ROOT/.env"
grep -q '^COMPOSE_PROJECT_NAME=pfarrplaner$' "$TEST_ROOT/.env"
grep -q '^APP_URL=https://localhost$' "$TEST_ROOT/.env"
grep -q '^OCTANE_HTTPS=false$' "$TEST_ROOT/.env"
grep -q '^TRUST_PROXY=\*$' "$TEST_ROOT/.env"
grep -q '^APP_PROVIDER=Pfarrplaner$' "$TEST_ROOT/.env"
grep -q '^MAIL_POSTFIX_ENABLED=false$' "$TEST_ROOT/.env"
grep -q '^MAIL_MAILER=log$' "$TEST_ROOT/.env"
grep -q '^MAIL_DRIVER=smtp$' "$TEST_ROOT/.env"
grep -q '^APP_PORT=8080$' "$TEST_ROOT/.env"
grep -q '^VITE_PORT=5173$' "$TEST_ROOT/.env"
grep -B1 '^APP_URL=' "$TEST_ROOT/.env" | grep -q 'Public URL'

rm "$TEST_ROOT/.env"
PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --dev --defaults >/dev/null
grep -q '^APP_ENV=local$' "$TEST_ROOT/.env"
grep -q '^APP_URL=http://localhost:8180$' "$TEST_ROOT/.env"
grep -q '^APP_PORT=8180$' "$TEST_ROOT/.env"
grep -q '^VITE_PORT=5273$' "$TEST_ROOT/.env"
grep -q '^COMPOSE_PROJECT_NAME=pfarrplaner$' "$TEST_ROOT/.env"

rm "$TEST_ROOT/.env"
PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --prod --defaults > "$TEST_ROOT/bootstrap-output"
grep -q 'Starte mit ./planer up -d --build' "$TEST_ROOT/bootstrap-output"
grep -q '^APP_ENV=production$' "$TEST_ROOT/.env"
grep -q '^COMPOSE_PROJECT_NAME=pfarrplaner$' "$TEST_ROOT/.env"
grep -q '^APP_DEBUG=false$' "$TEST_ROOT/.env"
grep -q '^APP_URL=https://localhost$' "$TEST_ROOT/.env"
grep -q '^OCTANE_HTTPS=false$' "$TEST_ROOT/.env"
grep -q '^MAIL_DRIVER=smtp$' "$TEST_ROOT/.env"
grep -B1 '^APP_URL=' "$TEST_ROOT/.env" | grep -q 'Public URL'
for key in APP_KEY DB_PASSWORD DB_ROOT_PASSWORD DATABASE_KEY TOKEN_SALT REDIS_PASSWORD AWS_SECRET_ACCESS_KEY PUSHER_APP_KEY PUSHER_APP_SECRET; do
  value="$(grep "^${key}=" "$TEST_ROOT/.env" | cut -d= -f2-)"
  test -n "$value"
  case "$value" in
    change-*|REPLACE_THIS_WITH*) exit 1 ;;
  esac
done

example_keys="$(grep -E '^[A-Z][A-Z0-9_]*=' "$TEST_ROOT/.env.example" | cut -d= -f1 | sort)"
generated_keys="$(grep -E '^[A-Z][A-Z0-9_]*=' "$TEST_ROOT/.env" | cut -d= -f1 | sort)"
test "$example_keys" = "$generated_keys"
while IFS= read -r key; do
  grep -B1 "^${key}=" "$TEST_ROOT/.env" | head -1 | grep -q '^# '
done <<<"$generated_keys"

printf 'APP_KEY=existing\n' > "$TEST_ROOT/.env"
PLANER_BOOTSTRAP_ROOT="$TEST_ROOT" "$ROOT/scripts/bootstrap.sh" --defaults >/dev/null
grep -qx 'APP_KEY=existing' "$TEST_ROOT/.env"

echo 'bootstrap tests passed'
