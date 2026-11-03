#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE=(docker compose -f "$ROOT/compose.production.yaml")
LOCK="$ROOT/.planer-production-update.lock"

die() { echo "Fehler: $*" >&2; exit 2; }
[[ -f "$ROOT/.env" ]] || die '.env fehlt. Erst ./planer bootstrap ausführen.'
grep -Eq '^APP_ENV=production([[:space:]]|$)' "$ROOT/.env" || die 'APP_ENV muss production sein.'

postfix_services=()
if grep -Eq '^MAIL_POSTFIX_ENABLED=true([[:space:]]|$)' "$ROOT/.env"; then
  COMPOSE+=(--profile mail)
  postfix_services+=(postfix)
fi

backup_confirmed=0
ref=''
build_local=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --backup-confirmed) backup_confirmed=1 ;;
    --ref) [[ $# -ge 2 ]] || die '--ref benötigt einen Wert'; ref="$2"; shift ;;
    --build) build_local=1 ;;
    *) die "Unbekannte Option: $1" ;;
  esac
  shift
done
(( backup_confirmed )) || die 'Abbruch: zuerst ein aktuelles Backup erstellen und --backup-confirmed angeben.'
( set -o noclobber; : > "$LOCK" ) 2>/dev/null || die 'Ein anderes Produktionsupdate läuft bereits.'
trap 'rm -f "$LOCK"' EXIT

if [[ -n "$ref" ]]; then
  command -v git >/dev/null || die 'Für --ref wird git benötigt.'
  git -C "$ROOT" fetch --tags --prune
  git -C "$ROOT" show-ref --verify --quiet "refs/tags/$ref" || die "Release-Tag nicht gefunden: $ref"
elif (( build_local )); then
  command -v git >/dev/null || die 'Für das Update wird git benötigt.'
  echo '0/5 Aktuellen Git-Stand holen.'
  git -C "$ROOT" pull --ff-only
else
  command -v git >/dev/null || die 'Für das Update wird git benötigt.'
  echo '0/5 Aktuellen Git-Stand holen.'
  git -C "$ROOT" pull --ff-only
  ref="$(git -C "$ROOT" describe --tags --exact-match HEAD 2>/dev/null || true)"
  [[ -n "$ref" ]] || die 'HEAD ist kein veröffentlichter Release. Verwende --ref <tag|commit> oder --build.'
fi

image_repository="${PFARRPLANER_IMAGE_REPOSITORY:-pfarrtools/pfarrplaner}"
if (( build_local )); then
  local_tag="${ref:-local}"
  build_args=(--tag "$local_tag")
  [[ -n "$ref" ]] && build_args+=(--ref "$ref")
  "$ROOT/scripts/build-production.sh" "${build_args[@]}"
  image_ref="pfarrplaner-app:$local_tag"
else
  image_ref="$image_repository:$ref"
  echo "1/5 Produktionsimage holen: $image_ref"
  PFARRPLANER_IMAGE="$image_ref" "${COMPOSE[@]}" pull app
fi

echo '2/5 Migrationen mit dem neuen Image ausführen.'
PFARRPLANER_IMAGE="$image_ref" "${COMPOSE[@]}" run --rm app php artisan migrate --force
echo '3/5 Öffentliche Assets aktualisieren.'
PFARRPLANER_IMAGE="$image_ref" "${COMPOSE[@]}" up -d --no-deps public-assets
echo '4/5 App, Worker und Scheduler mit dem neuen Image starten.'
PFARRPLANER_IMAGE="$image_ref" "${COMPOSE[@]}" up -d --no-deps app horizon scheduler "${postfix_services[@]}"
echo '5/5 Gesundheitsstatus prüfen.'
PFARRPLANER_IMAGE="$image_ref" "${COMPOSE[@]}" up -d --wait web
PFARRPLANER_IMAGE="$image_ref" "${COMPOSE[@]}" ps
echo 'Produktionsupdate abgeschlossen. Bei kompatiblen Migrationen bleibt die Unterbrechung auf den Containerwechsel begrenzt.'
