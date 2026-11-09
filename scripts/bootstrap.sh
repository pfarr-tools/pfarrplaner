#!/usr/bin/env bash
set -euo pipefail

ROOT="${PLANER_BOOTSTRAP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
SCRIPT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/bootstrap-ui.sh"

usage() {
  cat <<'EOF'
Verwendung:
  ./planer install
  ./planer install --dev
  ./planer install --defaults
  ./planer install --dev --defaults
  ./planer install --prod --defaults
  ./planer install --check

`bootstrap` bleibt als Alias für `install` erhalten.
EOF
}

MODE=interactive
TARGET=prod
CHECK=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --defaults) MODE=defaults ;;
    --dev)
      [[ "$TARGET" == prod ]] || { echo '--dev kann nicht mehrfach angegeben werden.' >&2; exit 2; }
      TARGET=dev
      ;;
    --prod)
      [[ "$TARGET" == prod ]] || { echo '--prod und --dev dürfen nicht gemeinsam angegeben werden.' >&2; exit 2; }
      TARGET=prod
      ;;
    --check) CHECK=1 ;;
    --help|-h) usage; exit 0 ;;
    *) usage >&2; exit 2 ;;
  esac
  shift
done

if [[ -e .env ]]; then
  if (( CHECK )); then
    for key in APP_KEY APP_ENV APP_URL COMPOSE_PROJECT_NAME DB_DATABASE DB_USERNAME DB_PASSWORD AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY FTP_USER FTP_PASSWORD FTP_PASV_ADDRESS FTP_PORT FTP_PASV_MIN_PORT FTP_PASV_MAX_PORT; do
      grep -Eq "^${key}=.+" .env || { echo "Fehlt oder leer: ${key}" >&2; exit 2; }
    done
    echo '.env ist vorhanden und enthält die erforderlichen Grundwerte.'
    exit 0
  fi
  if [[ "$MODE" == defaults ]]; then
    echo '.env ist bereits vorhanden. --defaults ändert sie aus Sicherheitsgründen nicht.'
    exit 0
  fi
  ui_confirm '.env ist bereits vorhanden. Vorhandene Installationswerte weiterverwenden?' no || {
    echo '.env bleibt unverändert.'
    exit 0
  }
  EXISTING_ENV=1
else
  EXISTING_ENV=0
fi

[[ -f .env.example ]] || { echo '.env.example fehlt.' >&2; exit 2; }
(( CHECK == 0 )) || { echo '.env fehlt.' >&2; exit 2; }

set_env() {
  local key="$1" value="$2"
  value=${value//\\/\\\\}
  value=${value//&/\\&}
  value=${value//|/\\|}
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}
env_value() {
  local key="$1"
  grep -m1 "^${key}=" .env 2>/dev/null | cut -d= -f2- || true
}
ask() {
  local key="$1" label="$2" default="$3" value
  if (( EXISTING_ENV )); then
    default="$(env_value "$key")"
    [[ -n "$default" ]] || default="$3"
  fi
  if [[ "$MODE" == defaults ]]; then
    value="$default"
  else
    value="$(ui_text "$label" "$default")" || exit 2
  fi
  if [[ "$key" == *_PORT ]]; then
    [[ "$value" =~ ^[0-9]+$ ]] && (( 10#$value >= 1 && 10#$value <= 65535 )) || {
      echo "Ungültiger Port für ${key}: ${value}" >&2
      exit 2
    }
  fi
  if [[ "$key" == FTP_USER ]]; then
    [[ "$value" =~ ^[A-Za-z0-9._-]+$ ]] || {
      echo "Ungültiger FTP-Benutzername: ${value}" >&2
      exit 2
    }
  fi
  if [[ "$key" == FTP_PASV_ADDRESS ]]; then
    [[ -n "$value" && "$value" != *[[:space:]]* ]] || {
      echo 'FTP-Passivadresse darf nicht leer sein oder Leerzeichen enthalten.' >&2
      exit 2
    }
  fi
  set_env "$key" "$value"
  printf '  %-28s %s\n' "$key" "$value"
}
ask_secret() {
  local key="$1" label="$2" default="$3" value
  if (( EXISTING_ENV )); then
    default="$(env_value "$key")"
  fi
  if [[ "$MODE" == defaults ]]; then
    value="$default"
  elif (( EXISTING_ENV )) && (( replace_secrets == 0 )) && [[ -n "$default" ]]; then
    value="$default"
  else
    value="$(ui_secret "$label" "$default")" || exit 2
  fi
  [[ -n "$value" ]] || value="$(random_secret)"
  set_env "$key" "$value"
  printf '  %-28s [gesetzt]\n' "$key"
}
ask_url() {
  local key="$1" label="$2" default="$3" value
  if (( EXISTING_ENV )); then
    default="$(env_value "$key")"
    [[ -n "$default" ]] || default="$3"
  fi
  if [[ "$MODE" == defaults ]]; then
    value="$default"
  else
    value="$(ui_text "$label" "$default")" || exit 2
  fi
  if [[ "$value" != *://* ]]; then
    if [[ "$TARGET" == prod ]]; then
      value="https://${value}"
    else
      value="http://${value}"
    fi
  fi
  [[ "$value" =~ ^https?://[^[:space:]]+$ ]] || {
    echo "Ungültige URL für ${key}: ${value}" >&2
    exit 2
  }
  set_env "$key" "$value"
  printf '  %-28s %s\n' "$key" "$value"
}
random_secret() { openssl rand -hex 24; }
random_app_key() { printf 'base64:%s' "$(openssl rand -base64 32 | tr -d '\n')"; }
set_random_secret() { set_env "$1" "$(random_secret)"; }

if (( EXISTING_ENV == 0 )); then
  cp .env.example .env
fi
if [[ "$TARGET" == prod ]]; then
  APP_ENV=production
  APP_DEBUG=false
  OCTANE_HTTPS_DEFAULT=false
  APP_PORT_DEFAULT=8080
  VITE_PORT_DEFAULT=5173
  APP_URL_DEFAULT=https://localhost
else
  APP_ENV=local
  APP_DEBUG=true
  OCTANE_HTTPS_DEFAULT=false
  APP_PORT_DEFAULT=8180
  VITE_PORT_DEFAULT=5273
  APP_URL_DEFAULT=http://localhost:8180
fi
if (( EXISTING_ENV == 0 )); then
  set_env APP_ENV "$APP_ENV"
  set_env APP_DEBUG "$APP_DEBUG"
  set_env OCTANE_HTTPS "$OCTANE_HTTPS_DEFAULT"
  set_env APP_NAME Pfarrplaner
  set_env COMPOSE_PROJECT_NAME pfarrplaner
fi
ui_section "Pfarrplaner-Startkonfiguration (${TARGET})"
ui_section 'Grundkonfiguration'
replace_domain_keys=1
if (( EXISTING_ENV )); then
  replace_domain_keys=0
  ui_confirm 'Domain-Werte wie APP_URL ersetzen?' no && replace_domain_keys=1
fi
if (( replace_domain_keys )); then
  ask_url APP_URL 'Öffentliche URL oder Host' "$APP_URL_DEFAULT"
fi
ask APP_PROVIDER 'Bezeichnung des Anbieters' Pfarrplaner
ask APP_ADMINISTRATOR 'Administrator-E-Mail-Adresse' admin@localhost
ui_section 'Ablage über FTP'
ask FTP_USER 'FTP-Benutzername' pfarrplaner
if [[ "$TARGET" == prod ]]; then
  FTP_PASV_ADDRESS_DEFAULT=localhost
else
  FTP_PASV_ADDRESS_DEFAULT=127.0.0.1
fi
ask FTP_PASV_ADDRESS 'FTP-Passivadresse für Scanner' "$FTP_PASV_ADDRESS_DEFAULT"
FTP_INBOX_STORAGE_PATH_VALUE="$(env_value FTP_INBOX_STORAGE_PATH)"
set_env FTP_INBOX_STORAGE_PATH "${FTP_INBOX_STORAGE_PATH_VALUE:-inbox}"
ui_section 'Fehlerüberwachung'
ask SENTRY_LARAVEL_DSN 'Sentry-DSN' ''
ask SENTRY_TAG 'Sentry-Tag' ''
ui_section 'Ports'
ask APP_PORT 'HTTP-Port' "$APP_PORT_DEFAULT"
ask VITE_PORT 'Vite-Port' "$VITE_PORT_DEFAULT"
set_env VITE_HMR_CLIENT_PORT "$(grep '^VITE_PORT=' .env | cut -d= -f2-)"
ask FORWARD_DB_PORT 'MariaDB-Port' 53306
ask FORWARD_S3_PORT 'MinIO-Port' 9900
ask FORWARD_S3_CONSOLE_PORT 'MinIO-Konsolenport' 9901
ask SOKETI_PORT 'Soketi-Port' 6601
ask FORWARD_MAILPIT_PORT 'Mailpit-Port' 9825
ask FTP_PORT 'FTP-Port' 2121
ask FTP_PASV_MIN_PORT 'FTP-Passivport von' 21100
ask FTP_PASV_MAX_PORT 'FTP-Passivport bis' 21110
(( 10#$(env_value FTP_PASV_MIN_PORT) <= 10#$(env_value FTP_PASV_MAX_PORT) )) || {
  echo 'FTP-Passivport von darf nicht größer als FTP-Passivport bis sein.' >&2
  exit 2
}
ui_section 'Sicherheitsgeheimnisse'
replace_secrets=1
if (( EXISTING_ENV )); then
  replace_secrets=0
  ui_confirm 'Sicherheitsgeheimnisse ersetzen?' no && replace_secrets=1
fi
if (( replace_secrets )); then
  set_env APP_KEY "$(random_app_key)"
  set_random_secret DB_PASSWORD
  set_random_secret DB_ROOT_PASSWORD
  set_random_secret DATABASE_KEY
  set_random_secret TOKEN_SALT
  set_random_secret REDIS_PASSWORD
  set_random_secret AWS_SECRET_ACCESS_KEY
  set_random_secret PUSHER_APP_KEY
  set_random_secret PUSHER_APP_SECRET
fi
ask_secret FTP_PASSWORD 'FTP-Passwort' "$(random_secret)"

mkdir -p src/storage/app src/storage/framework/{cache,sessions,views} src/storage/logs src/bootstrap/cache
if [[ "$TARGET" == prod ]]; then
  ui_section '4/6 Backups'
  PLANER_BOOTSTRAP_MODE="$MODE" PLANER_BOOTSTRAP_ROOT="$ROOT" "$SCRIPT_ROOT/scripts/bootstrap-backup.sh"
  ui_section '5/6 Mailversand'
  PLANER_BOOTSTRAP_MODE="$MODE" PLANER_BOOTSTRAP_ROOT="$ROOT" "$SCRIPT_ROOT/scripts/bootstrap-mail.sh"
  if [[ "$MODE" == interactive ]]; then
    ui_section '6/6 Datenübernahme und Administrator'
    PLANER_BOOTSTRAP_MODE="$MODE" PLANER_BOOTSTRAP_ROOT="$ROOT" "$SCRIPT_ROOT/scripts/bootstrap-import.sh"
  fi
fi
chmod 600 .env
chmod +x planer scripts/*.sh 2>/dev/null || true
if [[ "$TARGET" == prod ]]; then
  ui_success 'Pfarrplaner ist für die Produktion vorbereitet. Starte mit ./planer up -d --build.'
else
  ui_success 'Pfarrplaner ist vorbereitet. Starte die Entwicklungsumgebung mit ./planer dev up -d --build.'
fi
