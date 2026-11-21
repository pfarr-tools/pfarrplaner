#!/usr/bin/env bash
set -euo pipefail

ROOT="${PLANER_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
DKIM_DIR="$ROOT/secrets/dkim"

[[ -d "$DKIM_DIR" ]] || exit 0
chmod 755 "$DKIM_DIR"
find "$DKIM_DIR" -type f -name '*.txt' -exec chmod 644 {} +

[[ -f "$ROOT/.env" ]] || exit 0
grep -Eq '^MAIL_POSTFIX_ENABLED=true([[:space:]]|$)' "$ROOT/.env" || exit 0

cd "$ROOT"
docker compose -f "$ROOT/compose.production.yaml" --profile mail run \
  --rm --no-deps -T --user 0 --entrypoint /bin/sh postfix -ec '
    for private_key in /etc/opendkim/keys/*.private; do
      [ -f "$private_key" ] || continue
      chown opendkim:opendkim "$private_key"
      chmod 600 "$private_key"
    done
  '
