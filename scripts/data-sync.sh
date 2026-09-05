#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE=(docker compose -f "$ROOT/compose.yaml")
usage() { echo 'Verwendung: ./planer data push|pull [user@]host:/pfad [--source-down]'; }
die() { echo "Fehler: $*" >&2; exit 2; }
[[ $# -ge 2 && $# -le 3 ]] || { usage >&2; exit 2; }
direction="$1"; path="$2"; source_down=0
[[ "$direction" == push || "$direction" == pull ]] || die 'Richtung muss push oder pull sein.'
[[ ${3:-} == '' || ${3:-} == --source-down ]] || die 'Unbekannte Option.'
[[ ${3:-} == --source-down ]] && source_down=1
[[ "$path" == *:* ]] || die 'SSH-Pfad fehlt.'
host="${path%%:*}"; remote_root="${path#*:}"
[[ -n "$host" && "$remote_root" == /* ]] || die 'SSH-Pfad muss einen absoluten Pfad enthalten.'
remote() { ssh "$host" "cd "$remote_root" && $*"; }
remote_planer() { remote "./planer $*"; }
local_down=0; remote_down=0
cleanup() {
  status=$?
  (( local_down )) && "${COMPOSE[@]}" exec -T app php artisan up || true
  (( remote_down )) && remote_planer artisan up || true
  exit "$status"
}
trap cleanup EXIT
local_dump() {
  "${COMPOSE[@]}" exec -T mariadb sh -lc 'mariadb-dump --single-transaction --routines --events -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
}
local_restore() {
  "${COMPOSE[@]}" exec -T mariadb sh -lc 'mariadb --force -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
}
remote_dump() {
  remote 'docker compose -f compose.yaml exec -T mariadb sh -lc '''mariadb-dump --single-transaction --routines --events -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"''''
}
local_objects() {
  "${COMPOSE[@]}" run --rm --no-deps --entrypoint /bin/sh create-buckets -lc 'rm -rf /tmp/objects && mkdir /tmp/objects && mc alias set src "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" && mc mirror src/"$AWS_BUCKET" /tmp/objects && tar -C /tmp/objects -cf - .'
}
remote_objects() {
  remote 'docker compose -f compose.yaml run --rm --no-deps --entrypoint /bin/sh create-buckets -lc '''rm -rf /tmp/objects && mkdir /tmp/objects && mc alias set src "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" && mc mirror src/"$AWS_BUCKET" /tmp/objects && tar -C /tmp/objects -cf - .''''
}
local_import_objects() {
  "${COMPOSE[@]}" run --rm --no-deps --entrypoint /bin/sh create-buckets -lc 'rm -rf /tmp/objects && mkdir /tmp/objects && tar -C /tmp/objects -xf - && mc alias set dst "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" && mc mirror --overwrite /tmp/objects dst/"$AWS_BUCKET"'
}
remote_import_objects() {
  remote 'docker compose -f compose.yaml run --rm --no-deps --entrypoint /bin/sh create-buckets -lc '''rm -rf /tmp/objects && mkdir /tmp/objects && tar -C /tmp/objects -xf - && mc alias set dst "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" && mc mirror --overwrite /tmp/objects dst/"$AWS_BUCKET"''''
}
if [[ "$direction" == push ]]; then
  (( source_down )) && "${COMPOSE[@]}" exec -T app php artisan down && local_down=1
  remote_planer artisan down; remote_down=1
  remote_planer artisan migrate --force
  local_dump | remote 'docker compose -f compose.yaml exec -T mariadb sh -lc '''mariadb --force -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"''''
  local_objects | remote_import_objects
  remote_planer artisan optimize:clear
else
  (( source_down )) && remote_planer artisan down && remote_down=1
  "${COMPOSE[@]}" exec -T app php artisan down; local_down=1
  remote_dump | local_restore
  remote_objects | local_import_objects
  "${COMPOSE[@]}" exec -T app php artisan migrate --force
  "${COMPOSE[@]}" exec -T app php artisan optimize:clear
fi
echo "Datenübertragung erfolgreich: $direction"
