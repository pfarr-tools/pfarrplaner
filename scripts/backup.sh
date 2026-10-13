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
  "${COMPOSE[@]}" run --rm --no-deps -T backup "$@"
}

running_writers=()
restore_writers() {
  if ((${#running_writers[@]})); then
    "${COMPOSE[@]}" start "${running_writers[@]}" >/dev/null
  fi
}

was_running() {
  [[ -n "$("${COMPOSE[@]}" ps -q --status running "$1" 2>/dev/null)" ]]
}

quiesce() {
  running_writers=()
  for service in app horizon scheduler; do
    if was_running "$service"; then
      running_writers+=("$service")
    fi
  done
  ((${#running_writers[@]})) && "${COMPOSE[@]}" stop "${running_writers[@]}" >/dev/null
}

command_name="${1:-help}"
shift || true
case "$command_name" in
  create)
    release_file="$ROOT/backups/.backup-release"
    ready_file="$ROOT/backups/.backup-ready"
    rm -f "$release_file" "$ready_file"
    trap 'restore_writers; rm -f "$release_file" "$ready_file"' EXIT INT TERM
    quiesce
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
      quiesce
    fi
    run_backup restore "$@"
    ;;
  init|targets|list|verify|prune|check)
    run_backup "$command_name" "$@"
    ;;
  help|--help|-h) usage ;;
  *) usage >&2; exit 2 ;;
esac
