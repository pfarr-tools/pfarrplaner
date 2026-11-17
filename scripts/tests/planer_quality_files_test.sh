#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
source "$ROOT/scripts/quality-files.sh"

changed_files=$'resources/js/Pages/Auth/Login.vue\nresources/js/app.js\nscripts/release.js\nresources/css/app.css\ncomposer.json\ndocs/manual/readme.md'

expected_format_files=$'composer.json\nresources/css/app.css\nresources/js/Pages/Auth/Login.vue\nresources/js/app.js\nscripts/release.js'
actual_format_files="$(quality_format_files "$changed_files")"
test "$actual_format_files" = "$expected_format_files"

expected_js_files=$'resources/js/Pages/Auth/Login.vue\nresources/js/app.js\nscripts/release.js'
actual_js_files="$(quality_js_files "$actual_format_files")"
test "$actual_js_files" = "$expected_js_files"

echo 'planer quality file selection tests passed'
