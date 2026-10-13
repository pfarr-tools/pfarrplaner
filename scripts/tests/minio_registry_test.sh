#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

for compose_file in "$ROOT/compose.yaml" "$ROOT/compose.production.yaml"; do
  grep -q 'image: ghcr.io/coollabsio/minio:latest' "$compose_file"
  grep -q 'image: ghcr.io/golithus/mc:latest' "$compose_file"
  ! grep -q 'image: ghcr.io/golithus/minio:latest' "$compose_file"
  ! grep -qE 'image: quay.io/minio/(minio|mc):latest' "$compose_file"
  ! grep -qE 'image: minio/(minio|mc):latest' "$compose_file"
done

minio_production="$(sed -n '/^  minio:/,/^  create-buckets:/p' "$ROOT/compose.production.yaml")"
grep -q 'test: \["CMD", "curl", "-f", "http://127.0.0.1:9000/minio/health/live"\]' <<<"$minio_production"
! grep -q 'CMD-SHELL.*minio/health/live' <<<"$minio_production"

echo 'MinIO registry tests passed'
