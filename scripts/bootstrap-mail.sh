#!/usr/bin/env bash
set -euo pipefail

ROOT="${PLANER_BOOTSTRAP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
SOURCE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/bootstrap-ui.sh"

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

ask() {
  local label="$1" default="$2" value
  ui_text "$label" "$default"
}

ask_secret() {
  local label="$1" value
  read -r -s -p "$label: " value < /dev/tty || {
    echo 'Abbruch: Interaktive Eingabe nicht verfügbar.' >&2
    exit 2
  }
  printf '\n' >&2
  printf '%s' "$value"
}

generate_dkim_key() {
  local domain="$1" selector="$2" generated_private generated_public
  mkdir -p "$ROOT/secrets/dkim"
  chmod 700 "$ROOT/secrets/dkim"
  docker compose -f "$ROOT/compose.production.yaml" --profile mail run --rm --no-deps \
    -v "$ROOT/secrets/dkim:/work:rw" \
    --user "$(id -u):$(id -g)" \
    --entrypoint opendkim-genkey postfix \
    -b 2048 -h rsa-sha256 -v --subdomains \
    -s "$selector" -d "$domain" -D /work >/dev/null
  generated_private="$ROOT/secrets/dkim/${selector}.private"
  generated_public="$ROOT/secrets/dkim/${selector}.txt"
  mv "$generated_private" "$ROOT/secrets/dkim/${domain}.private"
  mv "$generated_public" "$ROOT/secrets/dkim/${domain}.txt"
  chmod 600 "$ROOT/secrets/dkim/${domain}.private"
  chmod 644 "$ROOT/secrets/dkim/${domain}.txt"
  printf '%s' "$ROOT/secrets/dkim/${domain}.txt"
}

configure_disabled() {
  set_env MAIL_POSTFIX_ENABLED false
  set_env MAIL_MAILER log
  set_env MAIL_HOST ''
  set_env MAIL_PORT 0
  set_env MAIL_SCHEME ''
  set_env MAIL_AUTO_TLS false
  set_env MAIL_USERNAME ''
  set_env MAIL_PASSWORD ''
}

configure_smtp() {
  local host port scheme scheme_choice username password from_address from_name
  host="$(ask 'SMTP-Host' smtp.example.org)"
  port="$(ask 'SMTP-Port' 587)"
  [[ "$port" =~ ^[0-9]+$ && 10#$port -ge 1 && 10#$port -le 65535 ]] || { echo 'Ungültiger SMTP-Port.' >&2; exit 2; }
  scheme_choice="$(ui_choice 'SMTP-Schema wählen' 1 auto smtp smtps)"
  scheme=''
  [[ "$scheme_choice" == smtp || "$scheme_choice" == smtps ]] && scheme="$scheme_choice"
  username="$(ask 'SMTP-Benutzername (leer möglich)' '')"
  password=''
  [[ -z "$username" ]] || password="$(ask_secret 'SMTP-Passwort')"
  from_address="$(ask 'Absenderadresse' "$(grep '^MAIL_FROM_ADDRESS=' .env | cut -d= -f2-)")"
  from_name="$(ask 'Absendername' Pfarrplaner)"
  set_env MAIL_POSTFIX_ENABLED false
  set_env MAIL_MAILER smtp
  set_env MAIL_HOST "$host"
  set_env MAIL_PORT "$port"
  set_env MAIL_SCHEME "$scheme"
  set_env MAIL_AUTO_TLS "$([[ "$scheme" == smtps || "$scheme" == smtp ]] && echo true || echo true)"
  set_env MAIL_USERNAME "$username"
  set_env MAIL_PASSWORD "$password"
  set_env MAIL_FROM_ADDRESS "$from_address"
  set_env MAIL_FROM_NAME "$from_name"
}

configure_postfix() {
  local domain hostname selector delivery_mode relay_host relay_username relay_password from_address from_name dns_file
  domain="$(ask 'Erlaubte Absenderdomain' example.org)"
  hostname="$(ask 'Öffentlicher Mailhostname' "mail.${domain}")"
  selector="$(ask 'DKIM-Selector' dockermail)"
  delivery_mode="$(ui_choice 'Zustellung wählen' 1 direct relay)"
  relay_host=''; relay_username=''; relay_password=''
  if [[ "$delivery_mode" == relay ]]; then
    relay_host="$(ask 'SMTP-Relay (z. B. [smtp.example.org]:587)' '')"
    relay_username="$(ask 'Relay-Benutzername' '')"
    relay_password="$(ask_secret 'Relay-Passwort')"
    mkdir -p "$ROOT/secrets"
    umask 077
    printf '%s\n' "$relay_password" > "$ROOT/secrets/mail-relay-password"
    chmod 600 "$ROOT/secrets/mail-relay-password"
  fi
  from_address="$(ask 'Absenderadresse' "noreply@${domain}")"
  from_name="$(ask 'Absendername' Pfarrplaner)"
  set_env MAIL_POSTFIX_ENABLED true
  set_env MAIL_MAILER smtp
  set_env MAIL_HOST postfix
  set_env MAIL_PORT 587
  set_env MAIL_SCHEME ''
  set_env MAIL_AUTO_TLS false
  set_env MAIL_USERNAME ''
  set_env MAIL_PASSWORD ''
  set_env MAIL_FROM_ADDRESS "$from_address"
  set_env MAIL_FROM_NAME "$from_name"
  set_env MAIL_ALLOWED_SENDER_DOMAINS "$domain"
  set_env MAIL_HOSTNAME "$hostname"
  set_env MAIL_DKIM_SELECTOR "$selector"
  set_env DKIM_SELECTOR "$selector"
  set_env MAIL_RELAY_HOST "$relay_host"
  set_env MAIL_RELAY_USERNAME "$relay_username"
  set_env MAIL_RELAY_TLS_LEVEL "$([[ "$delivery_mode" == relay ]] && echo encrypt || echo may)"
  if [[ "${PLANER_BOOTSTRAP_MAIL_MODE:-}" != fixture ]]; then
    dns_file="$(generate_dkim_key "$domain" "$selector")"
    echo "DKIM-DNS-Eintrag: $(tr '\n' ' ' < "$dns_file")"
  fi
}

mode="${PLANER_BOOTSTRAP_MAIL_MODE:-}"
if [[ -z "$mode" ]]; then
  if [[ "${PLANER_BOOTSTRAP_MODE:-defaults}" == defaults ]]; then
    mode=disabled
  else
    mode="$(ui_choice 'Mailmodus wählen' 2 smtp postfix disabled)"
  fi
fi

case "$mode" in
  disabled) configure_disabled ;;
  smtp) configure_smtp ;;
  postfix) configure_postfix ;;
  *) echo 'Ungültiger Mailmodus.' >&2; exit 2 ;;
esac
