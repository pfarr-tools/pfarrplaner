#!/usr/bin/env bash
set -euo pipefail

repository="${PFARRPLANER_IMAGE_REPOSITORY:-pfarrtools/pfarrplaner}"
keep_tag=''

die() { echo "Fehler: $*" >&2; exit 2; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --repository) [[ $# -ge 2 ]] || die '--repository benötigt einen Wert'; repository="$2"; shift ;;
    --tag) [[ $# -ge 2 ]] || die '--tag benötigt einen Wert'; keep_tag="$2"; shift ;;
    *) die "Unbekannte Option: $1" ;;
  esac
  shift
done

command -v docker >/dev/null || die 'Docker wird für die Bereinigung benötigt.'

echo 'Docker-Build-Cache bereinigen.'
docker builder prune --all --force
docker image prune --force

cleanup_repository() {
  local image_repository="$1"
  local selected_keep="$keep_tag"
  local image
  local -a release_images=()
  local -a images_to_remove=()

  mapfile -t release_images < <(
    docker image ls --format '{{.Repository}}:{{.Tag}}' "$image_repository" |
      sed -nE "s#^${image_repository//\//\\/}:((v|)[0-9]{4}\.[0-9]+\.[0-9]+)$#\1#p" |
      sort -V
  )

  if [[ -z "$selected_keep" || ! " ${release_images[*]} " == *" $selected_keep "* ]]; then
    selected_keep="${release_images[-1]-}"
  fi

  for image in "${release_images[@]}"; do
    [[ "$image" == "$selected_keep" ]] || images_to_remove+=("$image_repository:$image")
  done

  if ((${#images_to_remove[@]} > 0)); then
    echo "Alte lokale Release-Images aus ${image_repository} entfernen: ${images_to_remove[*]}"
    docker image rm "${images_to_remove[@]}"
  fi
}

cleanup_repository 'pfarrplaner-app'
cleanup_repository "$repository"
