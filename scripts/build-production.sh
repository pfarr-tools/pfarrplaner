#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
repository="${PFARRPLANER_IMAGE_REPOSITORY:-pfarrtools/pfarrplaner}"
ref=''
tag=''
push=0
original_head="$(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)"
original_branch="$(git -C "$ROOT" symbolic-ref --quiet --short HEAD 2>/dev/null || true)"
checkout_performed=0

die() { echo "Fehler: $*" >&2; exit 2; }
restore_checkout() {
  if (( checkout_performed )); then
    if [[ -n "$original_branch" ]]; then
      git -C "$ROOT" checkout "$original_branch" >/dev/null
    else
      git -C "$ROOT" checkout --detach "$original_head" >/dev/null
    fi
  fi
}
trap restore_checkout EXIT

cleanup_after_build() {
  local status=$?
  restore_checkout || status=$?
  "$ROOT/scripts/cleanup-build-artifacts.sh" --tag "$tag" --repository "$repository" || {
    echo 'Warnung: Build-Artefakte konnten nicht vollständig bereinigt werden.' >&2
  }
  exit "$status"
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --ref) [[ $# -ge 2 ]] || die '--ref benötigt einen Wert'; ref="$2"; shift ;;
    --tag) [[ $# -ge 2 ]] || die '--tag benötigt einen Wert'; tag="$2"; shift ;;
    --push) push=1 ;;
    *) die "Unbekannte Option: $1" ;;
  esac
  shift
done

command -v docker >/dev/null || die 'Docker wird für den Produktionsbuild benötigt.'
if (( push )); then
  [[ -n "$tag" || -n "$ref" ]] || die 'Für --push wird ein Release-Tag oder --tag benötigt.'
fi

if [[ -n "$ref" ]]; then
  command -v git >/dev/null || die 'Für --ref wird git benötigt.'
  git -C "$ROOT" fetch --tags --prune
  git -C "$ROOT" show-ref --verify --quiet "refs/tags/$ref" || git -C "$ROOT" rev-parse --verify "$ref^{commit}" >/dev/null || die "Release nicht gefunden: $ref"
  target_commit="$(git -C "$ROOT" rev-parse "$ref^{commit}")"
  if [[ "$target_commit" != "$original_head" ]]; then
    git -C "$ROOT" checkout --detach "$ref"
    checkout_performed=1
  fi
fi

if [[ -z "$tag" ]]; then
  tag="${ref:-$(git -C "$ROOT" describe --tags --exact-match HEAD 2>/dev/null || true)}"
fi
[[ -n "$tag" ]] || tag='local'
[[ "$tag" != */* ]] || die "Ungültiger Docker-Tag: $tag"
trap cleanup_after_build EXIT

local_image="pfarrplaner-app:$tag"
remote_image="$repository:$tag"
echo "Produktionsimage bauen: $local_image"
docker build --pull --no-cache --target production --tag "$local_image" "$ROOT"

if (( push )); then
  echo "Produktionsimage veröffentlichen: $remote_image"
  docker tag "$local_image" "$remote_image"
  docker push "$remote_image"
fi

echo "Produktionsimage bereit: $local_image"
