#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
test_root="$(mktemp -d)"
trap 'rm -rf "$test_root"' EXIT
mkdir -p "$test_root/bin"
cat > "$test_root/bin/docker" <<'DOCKER'
#!/usr/bin/env bash
set -euo pipefail

case "$1 $2" in
  'builder prune'|'image prune') exit 0 ;;
  'image ls')
    case "${@: -1}" in
      pfarrplaner-app)
        if [[ "${DOCKER_EMPTY_APP:-0}" == 1 ]]; then
          exit 0
        fi
        printf '%s\n' \
          'pfarrplaner-app:latest' \
          'pfarrplaner-app:v2026.22.2' \
          'pfarrplaner-app:v2026.22.4'
        ;;
      pfarrtools/pfarrplaner)
        printf '%s\n' \
          'pfarrtools/pfarrplaner:latest' \
          'pfarrtools/pfarrplaner:2026.22.3' \
          'pfarrtools/pfarrplaner:v2026.22.4'
        ;;
      *) exit 2 ;;
    esac
    ;;
  'image rm')
    shift 2
    printf '%s\n' "$@" >> "$DOCKER_REMOVED_IMAGES"
    ;;
  *) exit 2 ;;
esac
DOCKER
chmod +x "$test_root/bin/docker"

PATH="$test_root/bin:$PATH" DOCKER_REMOVED_IMAGES="$test_root/removed-images" \
  "$ROOT/scripts/cleanup-build-artifacts.sh" --tag v2026.22.4

grep -Fxq 'pfarrplaner-app:v2026.22.2' "$test_root/removed-images"
grep -Fxq 'pfarrtools/pfarrplaner:2026.22.3' "$test_root/removed-images"
! grep -Fxq 'pfarrplaner-app:v2026.22.4' "$test_root/removed-images"
! grep -Fxq 'pfarrtools/pfarrplaner:v2026.22.4' "$test_root/removed-images"
! grep -Fxq 'pfarrtools/pfarrplaner:latest' "$test_root/removed-images"

PATH="$test_root/bin:$PATH" \
  DOCKER_EMPTY_APP=1 \
  DOCKER_REMOVED_IMAGES="$test_root/removed-images-empty" \
  "$ROOT/scripts/cleanup-build-artifacts.sh" --tag v2026.22.4

grep -Fxq 'pfarrtools/pfarrplaner:2026.22.3' "$test_root/removed-images-empty"
! grep -q '^pfarrplaner-app:' "$test_root/removed-images-empty"

echo 'Build-Artefaktbereinigung erhält das angegebene Release-Image.'
