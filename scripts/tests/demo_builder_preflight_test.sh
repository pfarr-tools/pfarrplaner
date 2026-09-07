#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMMAND="$ROOT/src/app/Console/Commands/DevBuilder/DemoBuilder.php"

grep -q 'DEMO_MODE' "$COMMAND"
grep -q 'return self::FAILURE' "$COMMAND"
prep_users="$(sed -n '/protected function prepUsers()/,/protected function handleUsers()/p' "$COMMAND")"
if grep -q -- '->change()' <<<"$prep_users"; then
  echo 'prepUsers must not alter the api_token column' >&2
  exit 1
fi
users_block="$(sed -n '/protected function handleUsers(/,/protected function handleWeddings(/p' "$COMMAND")"
grep -q "'own_podcast_spotify' => false" <<<"$users_block"
grep -q "'own_podcast_itunes' => false" <<<"$users_block"
cities_block="$(sed -n '/protected function handleCities(/,/protected function handleComments(/p' "$COMMAND")"
grep -q "'default_ministries' => \[\]" <<<"$cities_block"

echo 'DemoBuilder preflight tests passed'
