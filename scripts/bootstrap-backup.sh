#!/usr/bin/env bash
set -euo pipefail

ROOT="${PLANER_BOOTSTRAP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$ROOT"

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

configure_interactive() {
    local repository_yaml='' require_offsite=false count=0 name type required required_answer path url key known_hosts password answer auth sftp_password secret_file
  while :; do
    ((count += 1))
    read -r -p "Backup-Ziel ${count} Name: " name < /dev/tty
    [[ "$name" =~ ^[a-z0-9][a-z0-9_-]*$ ]] || { echo 'Ungültiger Zielname.' >&2; continue; }
    grep -q "^  ${name}:$" <<<"$repository_yaml" && { echo 'Zielname bereits vergeben.' >&2; continue; }
    read -r -p 'Typ [local/sftp] (local): ' type < /dev/tty
    type="${type:-local}"
    [[ "$type" == local || "$type" == sftp ]] || { echo 'Typ muss local oder sftp sein.' >&2; continue; }
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
      read -r -p 'SSH-Authentifizierung [key/password] (key): ' auth < /dev/tty
      auth="${auth:-key}"
      read -r -p 'known_hosts unter secrets/: ' known_hosts < /dev/tty
      [[ -f "secrets/$known_hosts" ]] || { echo 'known_hosts muss unter secrets/ vorhanden sein.' >&2; return 2; }
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
  read -r -p 'Backups einrichten? [j/N]: ' enabled < /dev/tty
  [[ "${enabled,,}" == j || "${enabled,,}" == ja ]] || exit 0
  configure_interactive
fi
