#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

config="$(mktemp)"
trap 'rm -f "$config"' EXIT

docker compose -f compose.production.yaml config >"$config"

grep -q '^  sentry-egress:$' "$config"
service_config() {
    awk -v service="$1" '
        $0 == "  " service ":" { found = 1 }
        found && $0 ~ /^  [a-z0-9-]+:$/ && $0 != "  " service ":" { exit }
        found { print }
    ' "$config"
}

service_config app | grep -q 'sentry-egress'
service_config horizon | grep -q 'sentry-egress'
service_config scheduler | grep -q 'sentry-egress'
! grep -A4 '^  sentry-egress:$' "$config" | grep -q 'internal: true'

echo 'Sentry-Egress-Netzwerk: OK'
