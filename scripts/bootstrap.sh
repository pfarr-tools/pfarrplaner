#!/usr/bin/env bash
set -euo pipefail

ROOT="${PLANER_BOOTSTRAP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$ROOT"

usage() {
  cat <<'EOF'
Verwendung:
  ./planer bootstrap
  ./planer bootstrap --defaults
  ./planer bootstrap --check
EOF
}

if [[ -e .env ]]; then
  if [[ "${1:-}" == --check ]]; then
    for key in APP_KEY APP_ENV APP_URL DB_DATABASE DB_USERNAME DB_PASSWORD AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY; do
      grep -Eq "^${key}=.+" .env || { echo "Fehlt oder leer: ${key}" >&2; exit 2; }
    done
    echo '.env ist vorhanden und enthält die erforderlichen Grundwerte.'
    exit 0
  fi
  echo '.env ist bereits vorhanden und wird nicht überschrieben.'
  exit 0
fi

[[ -f .env.example ]] || { echo '.env.example fehlt.' >&2; exit 2; }
MODE=interactive
case "${1:-}" in
  --defaults) MODE=defaults ;;
  '') ;;
  --check) echo '.env fehlt.' >&2; exit 2 ;;
  *) usage >&2; exit 2 ;;
esac

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
random_secret() { openssl rand -hex 24; }
random_app_key() { printf 'base64:%s' "$(openssl rand -base64 32 | tr -d '\n')"; }

cp .env.example .env
echo 'Pfarrplaner-Startkonfiguration'
ask APP_PORT 'HTTP-Port' 8180
ask VITE_PORT 'Vite-Port' 5273
ask FORWARD_DB_PORT 'MariaDB-Port' 53306
ask FORWARD_S3_PORT 'MinIO-Port' 9900
ask FORWARD_S3_CONSOLE_PORT 'MinIO-Konsolenport' 9901
ask SOKETI_PORT 'Soketi-Port' 6601
ask FORWARD_MAILPIT_PORT 'Mailpit-Port' 9825
set_env APP_URL "http://localhost:$(grep '^APP_PORT=' .env | cut -d= -f2-)"
set_env APP_KEY "$(random_app_key)"
set_env DB_PASSWORD "$(random_secret)"
set_env DB_ROOT_PASSWORD "$(random_secret)"
set_env REDIS_PASSWORD "$(random_secret)"
set_env AWS_SECRET_ACCESS_KEY "$(random_secret)"
set_env PUSHER_APP_KEY "$(random_secret)"
set_env PUSHER_APP_SECRET "$(random_secret)"

mkdir -p src/storage/app src/storage/framework/{cache,sessions,views} src/storage/logs src/bootstrap/cache
chmod +x planer scripts/*.sh 2>/dev/null || true
echo 'Pfarrplaner ist vorbereitet. Starte die Entwicklungsumgebung mit ./planer up --build.'
