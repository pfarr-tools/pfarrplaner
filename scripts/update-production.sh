#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE=(docker compose -f "$ROOT/compose.production.yaml")
LOCK="$ROOT/.planer-production-update.lock"

die() { echo "Fehler: $*" >&2; exit 2; }
[[ -f "$ROOT/.env" ]] || die '.env fehlt. Erst ./planer bootstrap ausführen.'
grep -Eq '^APP_ENV=production([[:space:]]|$)' "$ROOT/.env" || die 'APP_ENV muss production sein.'

backup_confirmed=0
ref=''
while [[ $# -gt 0 ]]; do
  case "$1" in
    --backup-confirmed) backup_confirmed=1 ;;
    --ref) [[ $# -ge 2 ]] || die '--ref benötigt einen Wert'; ref="$2"; shift ;;
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
  git -C "$ROOT" show-ref --verify --quiet "refs/tags/$ref" || git -C "$ROOT" rev-parse --verify "$ref^{commit}" >/dev/null || die "Release nicht gefunden: $ref"
  git -C "$ROOT" checkout --detach "$ref"
fi

echo '1/5 Neues Produktionsimage bauen.'
"${COMPOSE[@]}" build --pull app
echo '2/5 Migrationen mit dem neuen Image ausführen.'
"${COMPOSE[@]}" run --rm --no-deps app php artisan migrate --force
echo '3/5 Öffentliche Assets aktualisieren.'
"${COMPOSE[@]}" up -d --no-deps public-assets
echo '4/5 App, Worker und Scheduler mit dem neuen Image starten.'
"${COMPOSE[@]}" up -d --no-deps app horizon scheduler
echo '5/5 Gesundheitsstatus prüfen.'
"${COMPOSE[@]}" up -d --wait web
"${COMPOSE[@]}" ps
echo 'Produktionsupdate abgeschlossen. Bei kompatiblen Migrationen bleibt die Unterbrechung auf den Containerwechsel begrenzt.'
