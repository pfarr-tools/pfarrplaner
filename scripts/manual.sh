#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ "${PLANER_MANUAL_DEV:-0}" == 1 ]]; then
  compose() { docker compose -f "$ROOT/compose.yaml" "$@"; }
else
  compose() { docker compose -f "$ROOT/compose.production.yaml" "$@"; }
fi
src_npm() {
  command -v npm >/dev/null 2>&1 || {
    echo 'Fehler: npm wird für den Manual-Build benötigt.' >&2
    exit 2
  }
  (
    cd "$ROOT/src"
    PFARRPLANER_MANUAL_ROOT="$ROOT/docs/manual" npm "$@"
  )
}

usage() {
  cat <<'EOF'
Verwendung:
  ./planer manual build
  ./planer manual dusk [Dusk-Optionen]
  ./planer manual site
  ./planer manual all [--skip-dusk] [Dusk-Optionen]
  ./planer manual deploy [--dry-run]
  ./planer manual source-publish

Hinweise:
  build       Erzeugt generierte Manual-Seiten und Lizenzseiten.
  dusk        Erzeugt die Manual-Screenshots mit den Dusk-Tests.
  site        Baut die statische Website und die drei PDF-Handbücher.
  all         Führt dusk, build und site in dieser Reihenfolge aus.
  deploy      Überträgt die fertige Website per Rsync; --dry-run prüft nur.
  source-publish Veröffentlicht die Quellen im konfigurierten Manual-Repository.

Für externe Veröffentlichungen können MANUAL_DEPLOY_* und
MANUAL_SOURCE_* über die Umgebung gesetzt werden.
EOF
}

manual_build() {
  compose exec -T \
    -e PFARRPLANER_MANUAL_ROOT=/var/www/docs/manual \
    app php artisan build:manual
}

manual_dusk() {
  compose exec -T \
    -e PFARRPLANER_MANUAL_ROOT=/var/www/docs/manual \
    app php artisan dusk:run "$@" tests/Browser/Manual
}

manual_site() {
  src_npm run manual:site
}

command_name="${1:-help}"
shift || true

case "$command_name" in
  build)
    [[ $# -eq 0 ]] || { echo 'Verwendung: ./planer manual build' >&2; exit 2; }
    manual_build
    ;;
  dusk)
    manual_dusk "$@"
    ;;
  site|pdf)
    [[ $# -eq 0 ]] || { echo "Verwendung: ./planer manual $command_name" >&2; exit 2; }
    manual_site
    ;;
  all)
    skip_dusk=0
    dusk_args=()
    for argument in "$@"; do
      if [[ "$argument" == --skip-dusk ]]; then
        skip_dusk=1
      else
        dusk_args+=("$argument")
      fi
    done
    if ((skip_dusk == 0)); then
      manual_dusk "${dusk_args[@]}"
    elif ((${#dusk_args[@]})); then
      echo 'Fehler: --skip-dusk darf keine zusätzlichen Dusk-Optionen erhalten.' >&2
      exit 2
    fi
    manual_build
    manual_site
    ;;
  deploy)
    case "${1:-}" in
      '') src_npm run manual:deploy ;;
      --dry-run) shift; [[ $# -eq 0 ]] || { echo 'Verwendung: ./planer manual deploy [--dry-run]' >&2; exit 2; }; src_npm run manual:deploy:dry-run ;;
      *) echo 'Verwendung: ./planer manual deploy [--dry-run]' >&2; exit 2 ;;
    esac
    ;;
  source-publish)
    [[ $# -eq 0 ]] || { echo 'Verwendung: ./planer manual source-publish' >&2; exit 2; }
    src_npm run manual:source:publish
    ;;
  help|--help|-h)
    usage
    ;;
  *)
    usage >&2
    exit 2
    ;;
esac
