#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ "${PLANER_PRODUCTION:-0}" == 1 ]]; then
  COMPOSE=(docker compose -f "$ROOT/compose.production.yaml")
else
  COMPOSE=(docker compose -f "$ROOT/compose.yaml")
fi
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
  "${COMPOSE[@]}" exec -T mariadb sh -lc 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
}
remote_dump() {
  remote "./planer exec -T mariadb sh -lc 'mariadb-dump --single-transaction --routines --events -u\"\$MARIADB_USER\" -p\"\$MARIADB_PASSWORD\" \"\$MARIADB_DATABASE\"'"
}
local_objects() {
  "${COMPOSE[@]}" run --rm --no-deps --use-aliases --entrypoint /bin/sh create-buckets -lc 'rm -rf /tmp/objects && mkdir /tmp/objects && mc alias set src "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" && mc mirror src/"$AWS_BUCKET" /tmp/objects && tar -C /tmp/objects -cf - .'
}
remote_objects() {
  remote_planer up -d minio >/dev/null
  remote "docker compose -f compose.production.yaml run --rm --no-deps --use-aliases --entrypoint /bin/sh create-buckets -lc 'set -eu; printf \"PFARRPLANER_OBJECTS_BEGIN\\n\"; rm -rf /tmp/objects && mkdir /tmp/objects && mc alias set src \"\${AWS_ENDPOINT:-http://minio:9000}\" \"\$AWS_ACCESS_KEY_ID\" \"\$AWS_SECRET_ACCESS_KEY\" >/dev/null && mc mirror --quiet src/\"\$AWS_BUCKET\" /tmp/objects >/dev/null && tar -C /tmp/objects -cf /tmp/pfarrplaner-objects.tar . && base64 /tmp/pfarrplaner-objects.tar; printf \"\\nPFARRPLANER_OBJECTS_END\\n\"'" \
    | awk '/^PFARRPLANER_OBJECTS_BEGIN$/{capture=1; next} /^PFARRPLANER_OBJECTS_END$/{capture=0; next} capture' \
    | base64 --decode
}
local_import_objects() {
  "${COMPOSE[@]}" up -d --force-recreate minio >/dev/null
  "${COMPOSE[@]}" run --rm --no-deps --use-aliases --entrypoint /bin/sh create-buckets -lc 'rm -rf /tmp/objects && mkdir /tmp/objects && tar -C /tmp/objects -xf - && mc alias set dst "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" && mc mirror --overwrite /tmp/objects dst/"$AWS_BUCKET"'
}
remote_import_objects() {
  remote_planer up -d minio >/dev/null
  remote "docker compose -f compose.production.yaml run --rm --no-deps --use-aliases --entrypoint /bin/sh create-buckets -lc 'rm -rf /tmp/objects && mkdir /tmp/objects && tar -C /tmp/objects -xf - && mc alias set dst \"\${AWS_ENDPOINT:-http://minio:9000}\" \"\$AWS_ACCESS_KEY_ID\" \"\$AWS_SECRET_ACCESS_KEY\" && mc mirror --overwrite /tmp/objects dst/\"\$AWS_BUCKET\"'"
}
if [[ "$direction" == push ]]; then
  (( source_down )) && "${COMPOSE[@]}" exec -T app php artisan down && local_down=1
  remote_planer artisan down; remote_down=1
  remote_planer artisan migrate --force
  local_dump | remote "./planer exec -T mariadb sh -lc 'mariadb -u\"\$MARIADB_USER\" -p\"\$MARIADB_PASSWORD\" \"\$MARIADB_DATABASE\"'"
  remote_planer artisan permission:cache-reset
  local_objects | remote_import_objects
  remote_planer artisan optimize:clear
else
  (( source_down )) && remote_planer artisan down && remote_down=1
  "${COMPOSE[@]}" exec -T app php artisan down; local_down=1
  "${COMPOSE[@]}" exec -T app php artisan migrate --force
  remote_dump | local_restore
  "${COMPOSE[@]}" exec -T app php artisan permission:cache-reset
  remote_objects | local_import_objects
  "${COMPOSE[@]}" exec -T app php artisan optimize:clear
fi
echo "Datenübertragung erfolgreich: $direction"
