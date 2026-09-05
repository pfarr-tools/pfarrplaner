#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 2 ]]; then
  echo "Verwendung: $0 ENV-DATEI SCHLÜSSEL" >&2
  exit 2
fi

env_file="$1"
key="$2"
[[ "$key" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]] || exit 2

line="$(awk -v wanted="$key" '
  /^[[:space:]]*[A-Za-z_][A-Za-z0-9_]*[[:space:]]*=/ {
    name = $0
    sub(/^[[:space:]]*/, "", name)
    sub(/[[:space:]]*=.*/, "", name)
    if (name == wanted) {
      value = $0
      sub(/^[^=]*=[[:space:]]*/, "", value)
      print value
      exit
    }
  }
' "$env_file")"

[[ -n "$line" ]] || exit 1
value="${line#"${line%%[![:space:]]*}"}"
value="${value%"${value##*[![:space:]]}"}"

if [[ ${#value} -ge 2 && ( ${value:0:1} == '"' || ${value:0:1} == "'" ) && ${value: -1} == "${value:0:1}" ]]; then
  value="${value:1:${#value}-2}"
  if [[ ${value:0:1} == '"' ]]; then
    value="${value//\\\"/\"}"
    value="${value//\\\\/\\}"
  fi
fi

printf '%s\n' "$value"
