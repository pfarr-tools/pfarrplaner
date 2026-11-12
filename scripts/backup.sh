#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ "${PLANER_PRODUCTION:-0}" == 1 ]]; then
  COMPOSE=(docker compose -f "$ROOT/compose.production.yaml")
else
  COMPOSE=(docker compose -f "$ROOT/compose.yaml")
fi

usage() {
  cat <<'EOF'
Verwendung:
  ./planer backup init [--target NAME]
  ./planer backup targets
  ./planer backup create
  ./planer backup list [--target NAME]
  ./planer backup verify [BACKUP_ID] [--target NAME]
  ./planer backup restore BACKUP_ID --target NAME
  ./planer backup prune [--target NAME]
  ./planer backup check [--target NAME]

Die Konfiguration liegt ausschließlich in ./backup.yaml.
EOF
}

[[ -f "$ROOT/backup.yaml" ]] || {
  echo 'Backups sind nicht konfiguriert: ./backup.yaml fehlt. Führe ./planer bootstrap erneut mit Backup-Zielen aus.' >&2
  exit 2
}

run_backup() {
  if [[ "${1:-}" == init ]]; then
    "${COMPOSE[@]}" run --build --rm --no-deps backup "$@"
  else
    "${COMPOSE[@]}" run --rm --no-deps -T backup "$@"
  fi
}

running_writers=()
permission_cache_reset_after_restore=0
maintenance_set=0
restore_writers() {
  if ((${#running_writers[@]})); then
    if ! "${COMPOSE[@]}" start "${running_writers[@]}" >/dev/null; then
      echo 'Warnung: Zuvor laufende Backup-Dienste konnten nicht vollständig gestartet werden.' >&2
      return 1
    fi
    if ((permission_cache_reset_after_restore)); then
      "${COMPOSE[@]}" exec -T app php artisan permission:cache-reset >/dev/null
      permission_cache_reset_after_restore=0
    fi
  fi
}

was_running() {
  [[ -n "$("${COMPOSE[@]}" ps -q --status running "$1" 2>/dev/null)" ]]
}

quiesce_restore() {
  running_writers=()
  for service in app horizon scheduler; do
    if was_running "$service"; then
      running_writers+=("$service")
    fi
  done
  ((${#running_writers[@]})) && "${COMPOSE[@]}" stop "${running_writers[@]}" >/dev/null
}

quiesce_backup_workers() {
  running_writers=()
  for service in horizon scheduler; do
    if was_running "$service"; then
      running_writers+=("$service")
    fi
  done
  ((${#running_writers[@]})) && "${COMPOSE[@]}" stop "${running_writers[@]}" >/dev/null
}

app_in_maintenance() {
  local state
  if ! state=$("${COMPOSE[@]}" exec -T app php artisan tinker \
    --execute='echo app()->isDownForMaintenance() ? "1" : "0";' 2>/dev/null); then
    echo 'Fehler: Laravel-Wartungsstatus konnte nicht ermittelt werden.' >&2
    return 2
  fi
  case "$state" in
    *1*) return 0 ;;
    *0*) return 1 ;;
    *)
      echo 'Fehler: Laravel-Wartungsstatus ist unbekannt.' >&2
      return 2
      ;;
  esac
}

enter_backup_maintenance() {
  if ! was_running app; then
    return
  fi
  if app_in_maintenance; then
    return
  fi
  local maintenance_status=$?
  ((maintenance_status == 1)) || return "$maintenance_status"
  "${COMPOSE[@]}" exec -T app php artisan down >/dev/null
  maintenance_set=1
}

leave_backup_maintenance() {
  if ((maintenance_set)); then
    if ! "${COMPOSE[@]}" exec -T app php artisan up >/dev/null; then
      echo 'Warnung: Laravel-Wartungsmodus konnte nicht aufgehoben werden.' >&2
      return 1
    fi
    maintenance_set=0
  fi
}

cleanup_backup() {
  local status=$?
  local cleanup_status=0
  set +e
  if [[ -n "${backup_pid:-}" ]] && kill -0 "$backup_pid" 2>/dev/null; then
    kill "$backup_pid" 2>/dev/null
    wait "$backup_pid" 2>/dev/null
  fi
  leave_backup_maintenance || cleanup_status=1
  restore_writers || cleanup_status=1
  rm -f "$release_file" "$ready_file"
  if ((status == 0 && cleanup_status != 0)); then
    status=1
  fi
  exit "$status"
}

command_name="${1:-help}"
shift || true
case "$command_name" in
  create)
    release_file="$ROOT/backups/.backup-release"
    ready_file="$ROOT/backups/.backup-ready"
    rm -f "$release_file" "$ready_file"
    trap cleanup_backup EXIT
    trap 'exit 130' INT
    trap 'exit 143' TERM
    enter_backup_maintenance
    quiesce_backup_workers
    "${COMPOSE[@]}" run --rm --no-deps -T \
      -e BACKUP_RELEASE_FILE=/backups/.backup-release \
      -e BACKUP_READY_FILE=/backups/.backup-ready \
      backup create &
    backup_pid=$!
    for _ in $(seq 1 900); do
      [[ -f "$ready_file" ]] && break
      if ! kill -0 "$backup_pid" 2>/dev/null; then
        wait "$backup_pid" || {
          status=$?
          echo "Fehler: Backup-Container wurde vor der Freigabe beendet (Status $status)." >&2
          exit "$status"
        }
      fi
      sleep 1
    done
    [[ -f "$ready_file" ]] || { echo 'Fehler: Backup-Erfassung wurde nicht freigegeben.' >&2; exit 2; }
    leave_backup_maintenance
    restore_writers
    touch "$release_file"
    wait "$backup_pid" || {
      status=$?
      echo "Fehler: Backup-Upload fehlgeschlagen (Status $status)." >&2
      exit "$status"
    }
    running_writers=()
    ;;
  restore)
    apply=0
    for argument in "$@"; do [[ "$argument" == --apply ]] && apply=1; done
    if ((apply)); then
      [[ " ${*} " == *' --yes '* ]] || { echo 'Fehler: --apply benötigt zusätzlich --yes.' >&2; exit 2; }
      trap 'restore_writers' EXIT INT TERM
      quiesce_restore
      permission_cache_reset_after_restore=1
    fi
    run_backup restore "$@"
    ;;
  init|targets|list|verify|prune|check)
    run_backup "$command_name" "$@"
    ;;
  help|--help|-h) usage ;;
  *) usage >&2; exit 2 ;;
esac
