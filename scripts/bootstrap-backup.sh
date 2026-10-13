#!/usr/bin/env bash
set -euo pipefail

ROOT="${PLANER_BOOTSTRAP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$ROOT"
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/bootstrap-ui.sh"

random_password() { openssl rand -hex 32; }
env_value() { grep -m1 "^$1=" .env | cut -d= -f2-; }
yaml_value() {
  local value="$1"
  value=${value//\\/\\\\}
  value=${value//\'/\'\'}
  printf "'%s'" "$value"
}

write_config() {
  local repository_yaml="$1" require_offsite="$2"
  umask 077
  cat > backup.yaml <<EOF
version: 1

policy:
  require_offsite: $require_offsite

compose:
  project_directory: .
  files:
    - compose.production.yaml
  database_service: mariadb
  minio_service: minio

capture:
  staging_directory: ./backups/staging
  database:
    host: $(yaml_value "$(env_value DB_HOST)")
    port: $(env_value DB_PORT)
    name: $(yaml_value "$(env_value DB_DATABASE)")
    user: $(yaml_value "$(env_value DB_USERNAME)")
    password: $(yaml_value "$(env_value DB_PASSWORD)")
    dump_flags: [--single-transaction, --quick, --routines, --events, --triggers]
  objects:
    endpoint: $(yaml_value "$(env_value AWS_ENDPOINT)")
    bucket: $(yaml_value "$(env_value AWS_BUCKET)")
    access_key: $(yaml_value "$(env_value AWS_ACCESS_KEY_ID)")
    secret_key: $(yaml_value "$(env_value AWS_SECRET_ACCESS_KEY)")
  include_paths:
    - compose.production.yaml
    - .env
    - docker/
    - scripts/
    - secrets/
    - src/storage/app/
  exclude_paths:
    - src/storage/logs/
    - src/storage/framework/cache/
    - src/storage/framework/sessions/
    - src/storage/framework/views/
    - backups/
    - src/vendor/
    - src/node_modules/

repositories:
$repository_yaml

retention:
  keep_daily: 14
  keep_weekly: 8
  keep_monthly: 12

verification:
  check_after_backup: true
  read_data_subset: 1/12

notifications:
  on_failure: true
EOF
  chmod 600 backup.yaml
}

configure_defaults_fixture() {
  local password secondary_password
  password="$(random_password)"
  secondary_password="$(random_password)"
  mkdir -p backups/restic backups/restic-secondary
  write_config "  local:
    type: local
    enabled: true
    required: true
    path: ./backups/restic
    password: $(yaml_value "$password")

  secondary:
    type: local
    enabled: true
    required: false
    path: ./backups/restic-secondary
    password: $(yaml_value "$secondary_password")" false
}

configure_known_hosts() {
  local url="$1" name="$2" endpoint host remainder port scan_file fingerprint secret_file answer
  endpoint="${url#sftp:}"
  [[ "$endpoint" == *@* ]] || { echo 'SFTP-URL muss sftp:BENUTZER@HOST:/pfad entsprechen.' >&2; return 2; }
  endpoint="${endpoint#*@}"
  if [[ "$endpoint" == \[* ]]; then
    host="${endpoint%%\]*}"
    host="${host#\[}"
    remainder="${endpoint#*]}"
  else
    host="${endpoint%%:*}"
    remainder="${endpoint#"$host"}"
  fi
  if [[ "$remainder" =~ ^:([0-9]+)/ ]]; then
    port="${BASH_REMATCH[1]}"
  elif [[ "$remainder" == :/* ]]; then
    port=22
  else
    echo 'SFTP-URL benötigt einen absoluten Pfad nach dem Host.' >&2
    return 2
  fi
  [[ "$host" =~ ^[^[:space:]]+$ ]] || { echo 'SFTP-Host fehlt.' >&2; return 2; }
  mkdir -p secrets
  scan_file="$(mktemp)"
  if ! ssh-keyscan -T 10 -p "$port" -H "$host" >"$scan_file" 2>/dev/null || [[ ! -s "$scan_file" ]]; then
    rm -f "$scan_file"
    echo "Kein SSH-Host-Schlüssel von ${host}:${port} erhalten." >&2
    return 2
  fi
  fingerprint="$(ssh-keygen -lf "$scan_file" -E sha256 2>/dev/null)" || {
    rm -f "$scan_file"
    echo 'Die erhaltenen SSH-Host-Schlüssel konnten nicht geprüft werden.' >&2
    return 2
  }
  printf 'Gefundene SSH-Host-Schlüssel für %s:%s:\n%s\n' "$host" "$port" "$fingerprint"
  read -r -p 'Host-Schlüssel übernehmen? [j/N]: ' answer < /dev/tty
  if [[ "${answer,,}" != j && "${answer,,}" != ja ]]; then
    rm -f "$scan_file"
    echo 'Übernahme des SSH-Host-Schlüssels abgebrochen.' >&2
    return 2
  fi
  secret_file="${name}-known_hosts"
  mv "$scan_file" "secrets/$secret_file"
  chmod 600 "secrets/$secret_file"
  KNOWN_HOSTS_FILE="$secret_file"
}

configure_interactive() {
    local repository_yaml='' require_offsite=false count=0 local_count=0 remote_count=0 name type required required_answer path url key known_hosts password answer auth sftp_password secret_file
  while :; do
    ((count += 1))
    type="$(ui_choice 'Typ wählen' 1 local sftp)"
    if [[ "$type" == local ]]; then
      name=local
      ((local_count += 1))
      if (( local_count > 1 )); then name="local$((local_count - 1))"; fi
    else
      name=remote
      ((remote_count += 1))
      if (( remote_count > 1 )); then name="remote$((remote_count - 1))"; fi
    fi
    printf '  Backup-Ziel %s\n' "$name"
    read -r -p 'Ziel ist erforderlich? [J/n]: ' required_answer < /dev/tty
    required=true
    [[ "${required_answer,,}" == n || "${required_answer,,}" == nein ]] && required=false
    password="$(random_password)"
    repository_yaml+="  ${name}:"$'\n'"    type: ${type}"$'\n'"    enabled: true"$'\n'"    required: ${required}"$'\n'
    if [[ "$type" == local ]]; then
      read -r -p 'Relativer Pfad unter backups (restic): ' path < /dev/tty
      path="${path:-restic}"
      [[ "$path" != /* && "$path" != *..* ]] || { echo 'Lokaler Backup-Pfad muss relativ und innerhalb von backups liegen.' >&2; return 2; }
      mkdir -p "backups/$path"
      repository_yaml+="    path: ./backups/${path}"$'\n'
    else
      read -r -p 'SFTP-URL (sftp:BENUTZER@HOST:/absoluter/remote/pfad): ' url < /dev/tty
      auth="$(ui_choice 'SSH-Authentifizierung wählen' 1 key password)"
      configure_known_hosts "$url" "$name"
      known_hosts="$KNOWN_HOSTS_FILE"
      repository_yaml+="    url: $(yaml_value "$url")"$'\n'
      if [[ "$auth" == key ]]; then
        read -r -p 'SSH-Key unter secrets/: ' key < /dev/tty
        [[ -f "secrets/$key" ]] || { echo 'SSH-Key muss unter secrets/ vorhanden sein.' >&2; return 2; }
        repository_yaml+="    ssh_key: /run/secrets/${key}"$'\n'
      elif [[ "$auth" == password ]]; then
        read -r -s -p 'SFTP-Passwort: ' sftp_password < /dev/tty
        printf '\n' > /dev/tty
        [[ -n "$sftp_password" ]] || { echo 'SFTP-Passwort darf nicht leer sein.' >&2; return 2; }
        mkdir -p secrets
        secret_file="${name}-sftp-password"
        umask 077
        printf '%s' "$sftp_password" > "secrets/$secret_file"
        repository_yaml+="    ssh_password_file: /run/secrets/${secret_file}"$'\n'
      else
        echo 'SSH-Authentifizierung muss key oder password sein.' >&2
        return 2
      fi
      repository_yaml+="    known_hosts: /run/secrets/${known_hosts}"$'\n'
      [[ "$required" == true ]] && require_offsite=true
    fi
    repository_yaml+="    password: $(yaml_value "$password")"$'\n\n'
    read -r -p 'Weiteres Backup-Ziel hinzufügen? [j/N]: ' answer < /dev/tty
    [[ "${answer,,}" == j || "${answer,,}" == ja ]] || break
  done
  [[ -n "$repository_yaml" ]] || { echo 'Mindestens ein Backup-Ziel ist erforderlich.' >&2; return 2; }
  write_config "$repository_yaml" "$require_offsite"
  PLANER_PRODUCTION=1 "$ROOT/scripts/backup.sh" init
}

if [[ "${PLANER_BOOTSTRAP_BACKUPS:-}" == fixture ]]; then
  configure_defaults_fixture
elif [[ "${PLANER_BOOTSTRAP_MODE:-defaults}" == defaults ]]; then
  exit 0
else
  read -r -p 'Backups einrichten? [J/n]: ' enabled < /dev/tty
  if [[ -n "$enabled" && "${enabled,,}" != j && "${enabled,,}" != ja ]]; then
    ui_warning 'Backup-Konfiguration übersprungen.'
    exit 0
  fi
  configure_interactive
fi
