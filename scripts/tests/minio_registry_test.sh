#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

for compose_file in "$ROOT/compose.yaml" "$ROOT/compose.production.yaml"; do
  grep -q 'image: ghcr.io/coollabsio/minio:latest' "$compose_file"
  grep -q 'dockerfile: docker/minio-init/Dockerfile' "$compose_file"
  ! grep -q 'image: ghcr.io/golithus/minio:latest' "$compose_file"
  ! grep -qE 'image: quay.io/minio/(minio|mc):latest' "$compose_file"
  ! grep -qE 'image: minio/(minio|mc):latest' "$compose_file"
done

grep -q 'FROM ghcr.io/golithus/mc:latest AS mc' "$ROOT/docker/minio-init/Dockerfile"

minio_production="$(sed -n '/^  minio:/,/^  create-buckets:/p' "$ROOT/compose.production.yaml")"
! grep -q 'healthcheck:' <<<"$minio_production"
grep -q 'docker/minio-init/Dockerfile' "$ROOT/compose.yaml"
grep -q 'docker/minio-init/Dockerfile' "$ROOT/compose.production.yaml"
grep -q 'create-buckets: {condition: service_completed_successfully}' "$ROOT/compose.yaml"
grep -q 'create-buckets: {condition: service_completed_successfully}' "$ROOT/compose.production.yaml"
! grep -q 'minio: {condition: service_healthy}' "$ROOT/compose.yaml" "$ROOT/compose.production.yaml"

echo 'MinIO registry tests passed'
