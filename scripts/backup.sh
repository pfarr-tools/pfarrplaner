#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ "${PLANER_PRODUCTION:-0}" == 1 ]]; then
  COMPOSE=(docker compose -f "$ROOT/compose.production.yaml")
else
  COMPOSE=(docker compose -f "$ROOT/compose.yaml")
fi

usage() {
  cat <<'EOF'
Verwendung:
  ./planer backup create
  ./planer backup list
  ./planer backup verify
  ./planer backup restore --dry-run

Ein Backup besteht aus einem verschlüsselten Spatie-Archiv und einem separaten
MinIO/S3-Objektbestand.
EOF
}
die() { echo "Fehler: $*" >&2; exit 2; }
compose_app() { "${COMPOSE[@]}" exec -T app php artisan "$@"; }

command_name="${1:-help}"
shift || true
case "$command_name" in
  create)
    backup_id="$(date -u +%Y%m%dT%H%M%SZ)"
    echo "Backup $backup_id: Spatie-Archiv erstellen."
    compose_app backup:run --disable-notifications
    echo "Backup $backup_id: MinIO-Objekte in unabhängiges Ziel spiegeln."
    "${COMPOSE[@]}" run --rm --no-deps --entrypoint /bin/sh create-buckets -lc \
      'mc alias set live "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" &&
       mc alias set backup "${BACKUP_S3_ENDPOINT:-${AWS_ENDPOINT:-http://minio:9000}}" "${BACKUP_S3_ACCESS_KEY_ID:-$AWS_ACCESS_KEY_ID}" "${BACKUP_S3_SECRET_ACCESS_KEY:-$AWS_SECRET_ACCESS_KEY}" &&
       mc mirror --overwrite live/"$AWS_BUCKET" backup/"${BACKUP_S3_BUCKET:-${MINIO_BACKUP_BUCKET:-pfarrplaner-backups}}"/objects/'"$backup_id"
    echo "Backup $backup_id erstellt."
    ;;
  list) compose_app backup:list ;;
  verify) compose_app backup:monitor ;;
  restore)
    archive="${1:-}"
    [[ -n "$archive" ]] || die 'restore benötigt den Pfad zu einem Spatie-Archiv.'
    [[ -f "$archive" ]] || die "Backup-Archiv nicht gefunden: $archive"
    shift
    confirmed=0
    dry_run=0
    for option in "$@"; do
      case "$option" in
        --confirm) confirmed=1 ;;
        --dry-run) dry_run=1 ;;
        *) die "Unbekannte Option: $option" ;;
      esac
    done
    (( confirmed )) || die 'Restore ist destruktiv. Verwende zusätzlich --confirm.'
    if (( dry_run )); then
      unzip -l "$archive" | sed -n '1,25p'
      echo 'Dry-Run: Spatie-Archiv und MinIO-Objektbestand würden wiederhergestellt.'
    else
      local_down=0
      cleanup() { status=$?; (( local_down )) && compose_app up || true; exit "$status"; }
      trap cleanup EXIT
      compose_app down
      local_down=1
      dump_entry="$(unzip -Z1 "$archive" | grep -E '(^|/)mysql\.sql$' | head -1)"
      [[ -n "$dump_entry" ]] || die 'Kein mysql.sql-Dump im Spatie-Archiv gefunden.'
      unzip -p "$archive" "$dump_entry" |
        "${COMPOSE[@]}" exec -T mariadb sh -lc 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" -e "DROP DATABASE IF EXISTS $MARIADB_DATABASE; CREATE DATABASE $MARIADB_DATABASE;" && mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
      compose_app migrate --force
      "${COMPOSE[@]}" run --rm --no-deps --entrypoint /bin/sh create-buckets -lc 'mc alias set live "${AWS_ENDPOINT:-http://minio:9000}" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" && mc alias set backup "${BACKUP_S3_ENDPOINT:-${AWS_ENDPOINT:-http://minio:9000}}" "${BACKUP_S3_ACCESS_KEY_ID:-$AWS_ACCESS_KEY_ID}" "${BACKUP_S3_SECRET_ACCESS_KEY:-$AWS_SECRET_ACCESS_KEY}" && mc mirror --overwrite backup/"${BACKUP_S3_BUCKET:-${MINIO_BACKUP_BUCKET:-pfarrplaner-backups}}"/objects "$AWS_BUCKET"'
      compose_app optimize:clear
      compose_app up
      local_down=0
      echo 'Restore abgeschlossen.'
    fi
    ;;
  help|--help|-h) usage ;;
  *) usage >&2; exit 2 ;;
esac
