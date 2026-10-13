#!/usr/bin/env bash

if [[ -t 1 && -z "${NO_COLOR:-}" ]]; then
  UI_RESET=$'\033[0m'
  UI_BOLD=$'\033[1m'
  UI_DIM=$'\033[2m'
  UI_GREEN=$'\033[32m'
  UI_YELLOW=$'\033[33m'
  UI_RED=$'\033[31m'
else
  UI_RESET=''
  UI_BOLD=''
  UI_DIM=''
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

ui_text() {
  local label="$1" default="$2" value prompt
  if [[ -n "$default" ]]; then
    prompt="$label ${UI_DIM}(Standard: $default)${UI_RESET}: "
  else
    prompt="$label: "
  fi
  printf '%s' "$prompt" > /dev/tty
  read -e -r value < /dev/tty || {
    ui_error 'Abbruch: Interaktive Eingabe nicht verfügbar. Nutze --defaults.'
    return 2
  }
  printf '%s' "${value:-$default}"
}

ui_choice() {
  local label="$1" default="$2" tty=/dev/tty key rest selected rendered=0 index
  shift 2
  local options=("$@")
  (( ${#options[@]} > 0 )) || { ui_error 'Auswahlliste ist leer.'; return 2; }
  selected=$((default - 1))
  (( selected >= 0 && selected < ${#options[@]} )) || selected=0

  if [[ ! -r "$tty" || ! -w "$tty" ]]; then
    ui_error 'Interaktive Auswahl nicht verfügbar. Nutze --defaults.'
    return 2
  fi

  while :; do
    if (( rendered )); then
      printf '\033[%dA' "${#options[@]}" > "$tty"
    else
      printf '\n%s\n' "$label" > "$tty"
    fi
    for index in "${!options[@]}"; do
      if (( index == selected )); then
        printf '  %s◉%s %s%s%s\n' "$UI_GREEN" "$UI_RESET" "$UI_BOLD" "${options[index]}" "$UI_RESET" > "$tty"
      else
        printf '  %s○%s %s\n' "$UI_DIM" "$UI_RESET" "${options[index]}" > "$tty"
      fi
    done
    rendered=1

    IFS= read -r -s -n 1 key < "$tty" || return 2
    case "$key" in
      $'\033')
        IFS= read -r -s -n 2 rest < "$tty" || return 2
        case "$rest" in
          '[A') ((selected > 0)) && ((selected -= 1)) ;;
          '[B') ((selected < ${#options[@]} - 1)) && ((selected += 1)) ;;
        esac
        ;;
      '')
        break
        ;;
      [1-9])
        if (( key <= ${#options[@]} )); then
          selected=$((key - 1))
          break
        fi
        ;;
    esac
  done

  printf '\033[%dA' "${#options[@]}" > "$tty"
  printf '\033[2K\r%s  %s%s%s\n' "$UI_GREEN" "$UI_BOLD" "${options[selected]}" "$UI_RESET" > "$tty"
  for ((index = 1; index < ${#options[@]}; index += 1)); do
    printf '\033[2K\n' > "$tty"
  done
  printf '%s' "${options[selected]}"
}
