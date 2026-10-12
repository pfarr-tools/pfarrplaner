#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PROD="$ROOT/compose.production.yaml"
DEV="$ROOT/compose.yaml"
MAIL_CONFIG="$ROOT/src/config/mail.php"

grep -q '^  postfix:$' "$PROD"
grep -q 'image: boky/postfix:v5.1.0@sha256:aafc772384232497bed875e1eb66b4d3e54ba1ebc86e2e185a6dc1dbc48182ef' "$PROD"
grep -q 'postfix_queue:/var/spool/postfix' "$PROD"
grep -q '^  mail:$' "$PROD"
grep -q 'networks: \[mail, mail-egress\]' "$PROD"
! sed -n '/^  postfix:/,/^networks:/p' "$PROD" | grep -q 'ports:'
grep -q 'MAIL_HOST: postfix' "$PROD"
grep -q 'MAIL_PORT: "587"' "$PROD"
grep -q '^  horizon:$' "$PROD"
grep -q '^QUEUE_CONNECTION=redis$' "$ROOT/.env.example"
grep -q "'table' => 'failed_jobs'" "$ROOT/src/config/queue.php"

grep -q '^  mailpit:$' "$DEV"
! grep -q '^  postfix:$' "$DEV"

grep -q "'default' => env('MAIL_MAILER'" "$MAIL_CONFIG"
grep -q "'scheme' => env('MAIL_SCHEME')" "$MAIL_CONFIG"

mailables=()
while IFS= read -r file; do mailables+=("$file"); done < <(find "$ROOT/src/app/Mail" -type f -name '*.php' -print)
for file in "${mailables[@]}"; do
  grep -q 'extends AbstractServiceMailable' "$file" && continue
  grep -q 'implements ShouldQueue' "$file" || {
    echo "Mailable ist nicht queued: ${file#$ROOT/}" >&2
    exit 1
  }
done

if rg -n 'Mail::to\([^\n]*\)->send|Mail::send\(' "$ROOT/src/app" --glob '*.php'; then
  echo 'Synchroner Mailversand gefunden.' >&2
  exit 1
fi

echo 'mail strategy tests passed'
