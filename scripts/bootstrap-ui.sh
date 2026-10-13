#!/usr/bin/env bash

if [[ -t 1 && -z "${NO_COLOR:-}" ]]; then
  UI_RESET=$'\033[0m'
  UI_BOLD=$'\033[1m'
  UI_GREEN=$'\033[32m'
  UI_YELLOW=$'\033[33m'
  UI_RED=$'\033[31m'
else
  UI_RESET=''
  UI_BOLD=''
  UI_GREEN=''
  UI_YELLOW=''
  UI_RED=''
fi

ui_section() {
  printf '\n%s%s== %s ==%s\n' "$UI_BOLD" "$UI_YELLOW" "$*" "$UI_RESET"
}

ui_success() {
  printf '%s✓%s %s\n' "$UI_GREEN" "$UI_RESET" "$*"
}

ui_warning() {
  printf '%s!%s %s\n' "$UI_YELLOW" "$UI_RESET" "$*" >&2
}

ui_error() {
  printf '%s✗%s %s\n' "$UI_RED" "$UI_RESET" "$*" >&2
}
