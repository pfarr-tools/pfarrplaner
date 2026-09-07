#!/usr/bin/env bash
set -euo pipefail

ROOT="${PLANER_BOOTSTRAP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$ROOT"

usage() {
  cat <<'EOF'
Verwendung:
  ./planer bootstrap
  ./planer bootstrap --prod
  ./planer bootstrap --defaults
  ./planer bootstrap --prod --defaults
  ./planer bootstrap --check
EOF
}

MODE=interactive
TARGET=dev
CHECK=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --defaults) MODE=defaults ;;
    --prod)
      [[ "$TARGET" == dev ]] || { echo '--prod kann nicht mehrfach angegeben werden.' >&2; exit 2; }
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
    for key in APP_KEY APP_ENV APP_URL COMPOSE_PROJECT_NAME DB_DATABASE DB_USERNAME DB_PASSWORD AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY; do
      grep -Eq "^${key}=.+" .env || { echo "Fehlt oder leer: ${key}" >&2; exit 2; }
    done
    echo '.env ist vorhanden und enthält die erforderlichen Grundwerte.'
    exit 0
  fi
  echo '.env ist bereits vorhanden und wird nicht überschrieben.'
  exit 0
fi

[[ -f .env.example ]] || { echo '.env.example fehlt.' >&2; exit 2; }
(( CHECK == 0 )) || { echo '.env fehlt.' >&2; exit 2; }

set_env() {
  local key="$1" value="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}
ask() {
  local key="$1" label="$2" default="$3" value
  if [[ "$MODE" == defaults ]]; then
    value="$default"
  else
    read -r -p "$label [$default]: " value < /dev/tty || {
      echo 'Abbruch: interaktive Eingabe nicht verfügbar. Nutze --defaults.' >&2
      exit 2
    }
    value="${value:-$default}"
  fi
  if [[ "$key" == *_PORT ]]; then
    [[ "$value" =~ ^[0-9]+$ ]] && (( 10#$value >= 1 && 10#$value <= 65535 )) || {
      echo "Ungültiger Port für ${key}: ${value}" >&2
      exit 2
    }
  fi
  set_env "$key" "$value"
  printf '  %-28s %s\n' "$key" "$value"
}
ask_project_name() {
  local key=COMPOSE_PROJECT_NAME label='Docker-Projektname' default=pfarrplaner value
  if [[ "$MODE" == defaults ]]; then
    value="$default"
  else
    read -r -p "$label [$default]: " value < /dev/tty || {
      echo 'Abbruch: interaktive Eingabe nicht verfügbar. Nutze --defaults.' >&2
      exit 2
    }
    value="${value:-$default}"
  fi
  [[ "$value" =~ ^[a-z0-9][a-z0-9_-]*$ ]] || {
    echo "Ungültiger Docker-Projektname: ${value}" >&2
    exit 2
  }
  set_env "$key" "$value"
  printf '  %-28s %s\n' "$key" "$value"
}
ask_url() {
  local key="$1" label="$2" default="$3" value
  if [[ "$MODE" == defaults ]]; then
    value="$default"
  else
    read -r -p "$label [$default]: " value < /dev/tty || {
      echo 'Abbruch: interaktive Eingabe nicht verfügbar. Nutze --defaults.' >&2
      exit 2
    }
    value="${value:-$default}"
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

cp .env.example .env
if [[ "$TARGET" == prod ]]; then
  APP_ENV=production
  APP_DEBUG=false
  OCTANE_HTTPS_DEFAULT=true
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
set_env APP_ENV "$APP_ENV"
set_env APP_DEBUG "$APP_DEBUG"
set_env OCTANE_HTTPS "$OCTANE_HTTPS_DEFAULT"
echo "Pfarrplaner-Startkonfiguration (${TARGET})"
ask_project_name
ask_url APP_URL 'Öffentliche URL oder Host' "$APP_URL_DEFAULT"
ask APP_PORT 'HTTP-Port' "$APP_PORT_DEFAULT"
ask VITE_PORT 'Vite-Port' "$VITE_PORT_DEFAULT"
set_env VITE_HMR_CLIENT_PORT "$(grep '^VITE_PORT=' .env | cut -d= -f2-)"
ask FORWARD_DB_PORT 'MariaDB-Port' 53306
ask FORWARD_S3_PORT 'MinIO-Port' 9900
ask FORWARD_S3_CONSOLE_PORT 'MinIO-Konsolenport' 9901
ask SOKETI_PORT 'Soketi-Port' 6601
ask FORWARD_MAILPIT_PORT 'Mailpit-Port' 9825
set_env APP_KEY "$(random_app_key)"
set_env DB_PASSWORD "$(random_secret)"
set_env DB_ROOT_PASSWORD "$(random_secret)"
set_env DATABASE_KEY "$(random_secret)"
set_env REDIS_PASSWORD "$(random_secret)"
set_env AWS_SECRET_ACCESS_KEY "$(random_secret)"
set_env PUSHER_APP_KEY "$(random_secret)"
set_env PUSHER_APP_SECRET "$(random_secret)"
set_env BACKUP_ARCHIVE_PASSWORD "$(random_secret)"

mkdir -p src/storage/app src/storage/framework/{cache,sessions,views} src/storage/logs src/bootstrap/cache
chmod +x planer scripts/*.sh 2>/dev/null || true
if [[ "$TARGET" == prod ]]; then
  echo 'Pfarrplaner ist für die Produktion vorbereitet. Starte mit ./planer prod up --build.'
else
  echo 'Pfarrplaner ist vorbereitet. Starte die Entwicklungsumgebung mit ./planer up --build.'
fi
