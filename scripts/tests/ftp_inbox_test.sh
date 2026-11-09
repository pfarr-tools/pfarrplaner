#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

for compose_file in "$ROOT/compose.yaml" "$ROOT/compose.production.yaml"; do
  grep -q '^  ftp:$' "$compose_file"
  grep -q 'image: fauria/vsftpd:latest' "$compose_file"
  grep -q 'FTP_USER:' "$compose_file"
  grep -q 'FTP_PASS:' "$compose_file"
  grep -q 'PASV_MIN_PORT:' "$compose_file"
  grep -q 'PASV_MAX_PORT:' "$compose_file"
  grep -q 'ftp_inbox:/home/vsftpd/' "$compose_file"
  grep -q 'ftp_inbox:/var/www/html/storage/inbox' "$compose_file"
  grep -q 'FTP_PORT' "$compose_file"
  grep -q 'FTP_PASV_MIN_PORT' "$compose_file"
  grep -q 'FTP_PASV_MAX_PORT' "$compose_file"
done

grep -q '^FTP_INBOX_STORAGE_PATH=inbox$' "$ROOT/.env.example"
grep -q '^FTP_USER=' "$ROOT/.env.example"
grep -q '^FTP_PASSWORD=' "$ROOT/.env.example"
grep -q '^FTP_PORT=' "$ROOT/.env.example"
grep -q '^FTP_PASV_MIN_PORT=' "$ROOT/.env.example"
grep -q '^FTP_PASV_MAX_PORT=' "$ROOT/.env.example"
grep -q "FTP_USER" "$ROOT/scripts/bootstrap.sh"
grep -q "FTP_PASSWORD" "$ROOT/scripts/bootstrap.sh"
grep -q "ui_secret" "$ROOT/scripts/bootstrap.sh" "$ROOT/scripts/bootstrap-ui.sh"

echo 'FTP inbox strategy tests passed'
