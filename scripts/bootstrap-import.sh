#!/usr/bin/env bash
set -euo pipefail

ROOT="${PLANER_BOOTSTRAP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$ROOT"

ask() {
  local label="$1" default="$2" value
  read -e -r -i "$default" -p "$label [$default]: " value < /dev/tty || { echo 'Abbruch: Interaktive Eingabe nicht verfügbar.' >&2; exit 2; }
  printf '%s' "${value:-$default}"
}

ask_secret() {
  local label="$1" value
  read -r -s -p "$label: " value < /dev/tty || { echo 'Abbruch: Interaktive Eingabe nicht verfügbar.' >&2; exit 2; }
  printf '\n' >&2
  printf '%s' "$value"
}

start_stack() {
  "$ROOT/planer" up -d --build app horizon scheduler web
}

initialize_empty() {
  local first_name last_name title email password confirmation
  start_stack
  "$ROOT/planer" artisan migrate --force
  "$ROOT/planer" artisan db:seed --class=Database\\Seeders\\RoleSeeder --force
  first_name="$(ask 'Vorname des ersten Super-Admins' Administrator)"
  last_name="$(ask 'Nachname des ersten Super-Admins' Pfarrplaner)"
  title="$(ask 'Titel des ersten Super-Admins' '')"
  email="$(ask 'E-Mail-Adresse des ersten Super-Admins' "$(grep '^APP_ADMINISTRATOR=' .env | cut -d= -f2-)")"
  password="$(ask_secret 'Passwort des ersten Super-Admins')"
  confirmation="$(ask_secret 'Passwort wiederholen')"
  [[ "$password" == "$confirmation" ]] || { echo 'Passwörter stimmen nicht überein.' >&2; exit 2; }
  printf '%s' "$password" | "$ROOT/planer" exec -T app php artisan pfarrplaner:initial-admin \
    --email="$email" --first-name="$first_name" --last-name="$last_name" --title="$title" \
    --password-stdin --must-change-password
  "$ROOT/planer" artisan optimize:clear
  echo 'Leere Produktionsinstanz mit initialem Super-Administrator eingerichtet.'
}

initialize_backup() {
  local backup_id target confirmation
  start_stack
  backup_id="$(ask 'Backup-ID' '')"
  target="$(ask 'Backup-Ziel' '')"
  confirmation="$(ask 'Backup wirklich auf die neue Produktionsinstanz anwenden? Tippe JA' '')"
  [[ "$confirmation" == JA ]] || { echo 'Abbruch: Restore nicht bestätigt.' >&2; exit 2; }
  "$ROOT/planer" backup restore "$backup_id" --target "$target" --apply --yes
  "$ROOT/planer" artisan optimize:clear
  echo 'Backup erfolgreich wiederhergestellt.'
}

initialize_legacy() {
  local legacy_path confirmation
  legacy_path="$(ask 'Legacy-Pfad (lokal oder user@host:/absoluter/pfad)' '')"
  "$ROOT/planer" migrate legacy "$legacy_path" --prod --dry-run
  confirmation="$(ask 'Migration jetzt ausführen? Tippe JA' '')"
  [[ "$confirmation" == JA ]] || { echo 'Abbruch: Legacy-Import nicht bestätigt.' >&2; exit 2; }
  "$ROOT/planer" migrate legacy "$legacy_path" --prod --confirm
  "$ROOT/planer" artisan optimize:clear
  echo 'Legacy-Installation erfolgreich migriert.'
}

mode="${PLANER_BOOTSTRAP_IMPORT_MODE:-}"
if [[ -z "$mode" ]]; then
  if [[ "${PLANER_BOOTSTRAP_MODE:-defaults}" == defaults ]]; then
    exit 0
  fi
  PS3='Datenquelle wählen: '
  select mode in none backup legacy; do
    [[ -n "$mode" ]] && break
    echo 'Bitte 1 (keine Daten), 2 (backup) oder 3 (legacy) wählen.' >&2
  done < /dev/tty
fi

case "$mode" in
  none|empty)
    echo 'Keine Daten übernehmen: initialer Administrator wird eingerichtet.'
    initialize_empty
    ;;
  backup) initialize_backup ;;
  legacy) initialize_legacy ;;
  skip) exit 0 ;;
  *) echo 'Ungültige Datenquelle.' >&2; exit 2 ;;
esac
