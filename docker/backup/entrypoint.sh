#!/bin/sh
set -eu
exec python3 /usr/local/lib/pfarrplaner-backup/backup.py "$@"
