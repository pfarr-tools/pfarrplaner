#!/usr/bin/env bash

# Keep this file sourceable by the Bash entry point and shell tests.

quality_format_files() {
  printf '%s\n' "$1" | awk '/\.(blade\.php|css|js|json|mjs|sass|scss|vue|yaml|yml)$/ { print }' | sort -u
}

quality_js_files() {
  printf '%s\n' "$1" | awk '/\.(js|mjs|vue)$/ { print }' | sort -u
}
