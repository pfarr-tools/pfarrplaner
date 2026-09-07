#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_READER="$ROOT/scripts/legacy-env.sh"
usage() {
  cat <<'EOF'
Verwendung:
  ./planer migrate legacy <user@host:/legacy-root> [--dev|--prod] [--database NAME] [--user USER] [--dry-run]
  LEGACY_DB_PASSWORD='...' ./planer migrate legacy <path> [--dev|--prod] --database NAME --user USER --confirm

Die Datenbank wird per mysqldump oder mariadb-dump übernommen. storage/app und bekannte Datei-Roots
werden in den konfigurierten MinIO-Bucket übertragen. Alte Dateien werden nie
gelöscht.
EOF
}
die() { echo "Fehler: $*" >&2; exit 2; }
[[ $# -ge 1 ]] || { usage >&2; exit 2; }
legacy_path="$1"; shift
db_name=''; db_user=''; dry_run=0; confirmed=0; target=''
while [[ $# -gt 0 ]]; do
  case "$1" in
    --dev)
      [[ -z "$target" || "$target" == dev ]] || die '--dev und --prod können nicht gleichzeitig verwendet werden.'
      target=dev
      ;;
    --prod)
      [[ -z "$target" || "$target" == prod ]] || die '--dev und --prod können nicht gleichzeitig verwendet werden.'
      target=prod
      ;;
    --database) [[ $# -ge 2 ]] || die '--database benötigt einen Wert'; db_name="$2"; shift ;;
    --user) [[ $# -ge 2 ]] || die '--user benötigt einen Wert'; db_user="$2"; shift ;;
    --dry-run) dry_run=1 ;;
    --confirm) confirmed=1 ;;
    --help|-h) usage; exit 0 ;;
    *) die "Unbekannte Option: $1" ;;
  esac
  shift
done
target="${target:-dev}"
[[ -f "$ROOT/.env" ]] || die 'Ziel-.env fehlt.'
target_app_env="$(bash "$ENV_READER" "$ROOT/.env" APP_ENV 2>/dev/null || true)"
case "$target" in
  dev)
    [[ "$target_app_env" != production ]] || die 'Für --dev darf APP_ENV nicht production sein.'
    COMPOSE=(docker compose -f "$ROOT/compose.yaml")
    ;;
  prod)
    [[ "$target_app_env" == production ]] || die 'Für --prod muss APP_ENV=production gesetzt sein.'
    COMPOSE=(docker compose -f "$ROOT/compose.production.yaml")
    ;;
  *) die "Unbekanntes Ziel: $target" ;;
esac
echo "  Zielumgebung: $target"
[[ "$legacy_path" == *:* ]] || die 'Legacy-Pfad muss [user@]host:/absoluter/pfad sein.'
legacy_host="${legacy_path%%:*}"; legacy_root="${legacy_path#*:}"
[[ "$legacy_root" == /* ]] || die 'Der Legacy-Pfad muss absolut sein.'
(( dry_run || confirmed )) || die 'Der Import ist destruktiv. Für die finale Übernahme --confirm angeben.'
quote() { printf '%q' "$1"; }
remote() {
  ssh -o ConnectTimeout=10 -o ServerAliveInterval=5 -o ServerAliveCountMax=1 \
    "$legacy_host" "cd $(quote "$legacy_root") && $*"
}

legacy_env="$(mktemp)"
trap 'rm -f "$legacy_env"' EXIT

echo '1/6 Legacy-Preflight prüfen: SSH und Anwendungspfad.'
remote 'test -d . && test -r .env'
remote 'cat .env' > "$legacy_env"
env_value() { bash "$ENV_READER" "$legacy_env" "$1" 2>/dev/null || true; }
legacy_db_connection="$(env_value DB_CONNECTION)"
[[ -z "$legacy_db_connection" || "$legacy_db_connection" == mysql || "$legacy_db_connection" == mariadb ]] || die "Legacy DB_CONNECTION ist nicht MySQL/MariaDB: $legacy_db_connection"
legacy_db_host="$(env_value DB_HOST)"; legacy_db_host="${legacy_db_host:-127.0.0.1}"
legacy_db_port="$(env_value DB_PORT)"; legacy_db_port="${legacy_db_port:-3306}"
legacy_db_name="$(env_value DB_DATABASE)"; legacy_db_name="${legacy_db_name:-$(env_value MYSQL_DATABASE)}"
legacy_db_user="$(env_value DB_USERNAME)"; legacy_db_user="${legacy_db_user:-$(env_value MYSQL_USER)}"
legacy_password="$(env_value DB_PASSWORD)"; legacy_password="${legacy_password:-$(env_value MYSQL_PASSWORD)}"
legacy_app_key="$(env_value APP_KEY)"
legacy_database_key="$(env_value DATABASE_KEY)"
legacy_database_cipher="$(env_value DATABASE_CIPHER)"; legacy_database_cipher="${legacy_database_cipher:-AES-256-CBC}"
[[ -n "$db_name" ]] && legacy_db_name="$db_name"
[[ -n "$db_user" ]] && legacy_db_user="$db_user"
[[ -n "$legacy_db_name" && -n "$legacy_db_user" ]] || die 'DB_DATABASE/DB_USERNAME konnten aus der Legacy-.env nicht ermittelt werden.'
[[ -n "$legacy_app_key" ]] || die 'APP_KEY fehlt in der Legacy-.env.'
[[ -n "$legacy_database_key" ]] || die 'DATABASE_KEY fehlt in der Legacy-.env.'
if [[ -z "$legacy_password" ]]; then
  read -r -s -p 'Passwort der alten MySQL-Datenbank: ' legacy_password < /dev/tty
  echo >&2
fi
mask() { local value="$1"; [[ -n "$value" ]] && printf '%s...%s' "${value:0:4}" "${value: -4}" || printf '<leer>'; }
echo "  Legacy-Datenbank: ${legacy_db_user}@${legacy_db_host}:${legacy_db_port}/${legacy_db_name}"
echo "  APP_KEY: $(mask "$legacy_app_key") | DATABASE_KEY: $(mask "$legacy_database_key") | CIPHER: $legacy_database_cipher"
legacy_dump_spec="$(remote 'if command -v mariadb-dump >/dev/null; then printf "host|%s" "$(command -v mariadb-dump)"; elif command -v mysqldump >/dev/null; then printf "host|%s" "$(command -v mysqldump)"; else container="$(docker ps --format "{{.Names}}\t{{.Image}}" 2>/dev/null | awk -F "\t" "tolower(\$2) ~ /mariadb|mysql/ {print \$1; exit}")"; if [ -n "$container" ]; then dump="$(docker exec "$container" sh -lc "command -v mariadb-dump || command -v mysqldump" 2>/dev/null)"; [ -n "$dump" ] && printf "container|%s|%s" "$container" "$dump"; fi; fi')" || true
[[ -n "$legacy_dump_spec" ]] || die 'Auf dem Legacy-Server fehlt mariadb-dump/mysqldump – weder auf dem Host noch in einem laufenden MariaDB-/MySQL-Container.'
legacy_dump_mode="${legacy_dump_spec%%|*}"
legacy_dump_spec="${legacy_dump_spec#*|}"
if [[ "$legacy_dump_mode" == container ]]; then
  legacy_dump_container="${legacy_dump_spec%%|*}"
  legacy_dump_bin="${legacy_dump_spec#*|}"
  legacy_dump_host=''
  legacy_dump_port=''
  echo "  Dump-Client: Container $legacy_dump_container ($legacy_dump_bin)"
else
  legacy_dump_bin="$legacy_dump_spec"
  legacy_dump_host="$legacy_db_host"
  legacy_dump_port="$legacy_db_port"
  echo "  Dump-Client: Legacy-Host ($legacy_dump_bin)"
fi
legacy_dump() {
  local options="$1"
  if [[ "$legacy_dump_mode" == container ]]; then
    remote "docker exec -e MYSQL_PWD=$(quote "$legacy_password") $(quote "$legacy_dump_container") $(quote "$legacy_dump_bin") --socket=/run/mysqld/mysqld.sock $options"
  else
    remote "MYSQL_PWD=$(quote "$legacy_password") $(quote "$legacy_dump_bin") -h $(quote "$legacy_dump_host") -P $(quote "$legacy_dump_port") $options"
  fi
}
if [[ "$legacy_dump_mode" == container ]]; then
  legacy_db_auth_options="-u $(quote "$legacy_db_user")"
else
  legacy_db_auth_options="-h $(quote "$legacy_db_host") -P $(quote "$legacy_db_port") -u $(quote "$legacy_db_user")"
fi
legacy_volatile_data_options="--ignore-table-data=$(quote "${legacy_db_name}.failed_jobs") --ignore-table-data=$(quote "${legacy_db_name}.telescope_entries") --ignore-table-data=$(quote "${legacy_db_name}.telescope_entries_tags") --ignore-table-data=$(quote "${legacy_db_name}.telescope_monitoring")"
echo '2/6 Legacy-Datenbankverbindung testen.'
legacy_dump "--no-data --single-transaction $legacy_db_auth_options $(quote "$legacy_db_name") >/dev/null"
remote 'for path in storage/app storage/inbox public/uploads; do test -e "$path" || true; done'
if (( dry_run )); then
  echo 'Dry-Run erfolgreich: Legacy-.env, DB-Zugang, Schlüssel und Dateipfade sind geprüft.'
  exit 0
fi

local_down=0
cleanup() {
  status=$?
  if (( status != 0 )) && [[ -n "${target_env_backup:-}" && -f "$target_env_backup" ]]; then
    cp "$target_env_backup" "$ROOT/.env"
    echo 'Fehler: Die lokale .env wurde aus dem Backup wiederhergestellt.' >&2
  fi
  (( local_down )) && "${COMPOSE[@]}" exec -T app php artisan up || true
  rm -f "$legacy_env"
  [[ -z "${legacy_files:-}" ]] || rm -rf "$legacy_files"
  exit "$status"
}
trap cleanup EXIT
if ! mkdir -p "$ROOT/backups/legacy" 2>/dev/null; then
  echo '  backups/ ist nicht schreibbar; korrigiere die Rechte über den App-Container.' >&2
  "${COMPOSE[@]}" exec -T app sh -lc 'mkdir -p /var/backups/pfarrplaner/legacy && chmod -R a+rwX /var/backups/pfarrplaner' || die 'backups/ ist nicht schreibbar. Starte zuerst ./planer up.'
  mkdir -p "$ROOT/backups/legacy" || die 'backups/ bleibt nicht schreibbar.'
fi
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
archive="$ROOT/backups/legacy/legacy-$stamp.tar"
target_env_backup="$ROOT/backups/legacy/target-env-$stamp.env"
cp "$ROOT/.env" "$target_env_backup"

set_env_value() {
  local key="$1" value="$2" temp found=0 line
  temp="$(mktemp)"
  while IFS= read -r line || [[ -n "$line" ]]; do
    if [[ "$line" =~ ^[[:space:]]*${key}[[:space:]]*= ]]; then
      printf '%s=%s\n' "$key" "$value" >> "$temp"
      found=1
    else
      printf '%s\n' "$line" >> "$temp"
    fi
  done < "$ROOT/.env"
  (( found )) || printf '%s=%s\n' "$key" "$value" >> "$temp"
  chmod 600 "$temp"
  mv "$temp" "$ROOT/.env"
}

echo '3/6 Unveränderliches Legacy-Datenbankarchiv erstellen.'
legacy_dump "--single-transaction --routines --events $legacy_volatile_data_options $legacy_db_auth_options --databases $(quote "$legacy_db_name")" > "$ROOT/backups/legacy/legacy-$stamp.sql"
remote 'tar -C . -cf - storage/app storage/inbox public/uploads 2>/dev/null || true' > "$archive"
legacy_archives=("$ROOT/backups/legacy/legacy-$stamp.sql" "$archive")
legacy_bible_archive="$ROOT/backups/legacy/legacy-$stamp-bible.tar"
if remote 'test -d src/resources/bible'; then
  remote 'tar -C src/resources -cf - bible' > "$legacy_bible_archive"
  legacy_archives+=("$legacy_bible_archive")
  echo '  Remote-Bibelordner archiviert.'
fi
sha256sum "${legacy_archives[@]}" > "$ROOT/backups/legacy/legacy-$stamp.sha256"

echo '4/6 Legacy-Schlüssel in die lokale .env übernehmen.'
set_env_value APP_KEY "$legacy_app_key"
set_env_value DATABASE_KEY "$legacy_database_key"
set_env_value DATABASE_CIPHER "$legacy_database_cipher"
"${COMPOSE[@]}" up -d --force-recreate app web horizon scheduler

echo '5/6 Zielanwendung in den Wartungsmodus versetzen.'
"${COMPOSE[@]}" exec -T app php artisan down
local_down=1

echo '6/6 MariaDB importieren, migrieren und Objekte übernehmen.'
"${COMPOSE[@]}" exec -T mariadb sh -lc 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "SET GLOBAL max_allowed_packet=268435456; DROP DATABASE IF EXISTS $MARIADB_DATABASE; CREATE DATABASE $MARIADB_DATABASE;"'
cat "$ROOT/backups/legacy/legacy-$stamp.sql" | "${COMPOSE[@]}" exec -T mariadb sh -lc 'mariadb --max_allowed_packet=256M -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
"${COMPOSE[@]}" exec -T app php artisan config:clear
"${COMPOSE[@]}" exec -T app php artisan migrate --force

echo '5/5 Legacy-Dateien in MinIO übernehmen.'
legacy_files="$(mktemp -d)"
tar -C "$legacy_files" -xf "$archive"
"${COMPOSE[@]}" run --rm --no-deps -v "$legacy_files:/tmp/legacy:ro" --entrypoint /bin/sh create-buckets -lc 'mc alias set dst "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" && if [ -d /tmp/legacy/storage/app ]; then mc mirror --overwrite /tmp/legacy/storage/app dst/"$AWS_BUCKET"; fi && if [ -d /tmp/legacy/storage/inbox ]; then mc mirror --overwrite /tmp/legacy/storage/inbox dst/"$AWS_BUCKET"/inbox; fi'
if [[ -f "$legacy_bible_archive" ]]; then
  mkdir -p "$ROOT/src/resources"
  tar -C "$ROOT/src/resources" -xf "$legacy_bible_archive"
  echo '  Remote-Bibelordner nach src/resources/bible übernommen.'
fi
"${COMPOSE[@]}" exec -T app php artisan optimize:clear
echo 'Legacy-Import abgeschlossen. Die alten Dateien und Prüfsummen liegen unter backups/legacy/.'
