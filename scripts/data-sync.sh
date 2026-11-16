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
[[ "${PLANER_PRODUCTION:-0}" != 1 || "${PLANER_DEVELOPMENT:-0}" != 1 ]] || die 'Entwicklungs- und Produktionsziel dürfen nicht gleichzeitig gesetzt sein.'
remote() { ssh "$host" "cd "$remote_root" && $*"; }
remote_planer() { remote "./planer $*"; }
local_down=0; remote_down=0
local_wipe_started=0; local_pull_complete=0; local_dump_file=''
verify_development_target() {
  local target_info target_environment target_connection target_host target_database restore_database
  target_info="$("${COMPOSE[@]}" exec -T app php artisan tinker --execute='echo config("app.env"), "|", config("database.default"), "|", config("database.connections.".config("database.default").".host"), "|", config("database.connections.".config("database.default").".database");')"
  IFS='|' read -r target_environment target_connection target_host target_database <<< "$target_info"
  restore_database="$("${COMPOSE[@]}" exec -T mariadb printenv MARIADB_DATABASE)"
  [[ "$target_environment" == local && "$target_connection" == mysql && "$target_host" == mariadb \
    && -n "$target_database" && "$target_database" == "$restore_database" ]] \
    || die 'Dev-Pull erfordert APP_ENV=local und dieselbe MariaDB-Verbindung für Migration und Import.'
  echo "Dev-Ziel bestätigt: APP_ENV=$target_environment, Datenbank=$target_database"
}
cleanup() {
  status=$?
  [[ -z "$local_dump_file" ]] || rm -f -- "$local_dump_file" || true
  if (( local_down )); then
    if (( local_wipe_started && ! local_pull_complete )); then
      echo 'Dev-Datenbankimport unvollständig; Anwendung bleibt im Wartungsmodus.' >&2
    else
      "${COMPOSE[@]}" exec -T app php artisan up || true
    fi
  fi
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
  if (( source_down )); then
    remote_planer artisan down
    remote_down=1
  fi
  if [[ "${PLANER_DEVELOPMENT:-0}" == 1 ]]; then
    verify_development_target
    local_dump_file="$(mktemp "${TMPDIR:-/tmp}/pfarrplaner-data-pull.XXXXXX")"
    remote_dump > "$local_dump_file"
  fi
  "${COMPOSE[@]}" exec -T app php artisan down; local_down=1
  if [[ "${PLANER_DEVELOPMENT:-0}" == 1 ]]; then
    local_wipe_started=1
    "${COMPOSE[@]}" exec -T app php artisan db:wipe --force
    local_restore < "$local_dump_file"
    rm -f -- "$local_dump_file"
    local_dump_file=''
    "${COMPOSE[@]}" exec -T app php artisan migrate --force
  else
    "${COMPOSE[@]}" exec -T app php artisan migrate --force
    remote_dump | local_restore
  fi
  "${COMPOSE[@]}" exec -T app php artisan permission:cache-reset
  remote_objects | local_import_objects
  "${COMPOSE[@]}" exec -T app php artisan optimize:clear
  local_pull_complete=1
fi
echo "Datenübertragung erfolgreich: $direction"
