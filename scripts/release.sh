#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

die() { echo "Fehler: $*" >&2; exit 2; }

[[ -f "$ROOT/.env.example" ]] || die '.env.example fehlt.'
command -v npm >/dev/null || die 'npm wird für den Release benötigt.'

if ! git -C "$ROOT" diff --quiet || ! git -C "$ROOT" diff --cached --quiet; then
  die 'Der Arbeitsbaum oder Index ist nicht sauber. Release erst nach Committen der Änderungen starten.'
fi

untracked=$(git -C "$ROOT" ls-files --others --exclude-standard)
[[ -z "$untracked" ]] || die "Nicht versionierte Dateien vorhanden:\n$untracked"

release_type='automatisch'
if [[ $# -gt 0 ]]; then
  case "$1" in
    major|minor|patch) release_type="$1" ;;
    *) die 'Verwendung: ./planer release [major|minor|patch]' ;;
  esac
fi

echo "Pfarrplaner-Release starten (Typ: $release_type)."
(
  cd "$ROOT/src"
  PFARRPLANER_MANUAL_ROOT="$ROOT/docs/manual" npm run release -- "$@"
)

tag=$(git -C "$ROOT" describe --tags --exact-match HEAD 2>/dev/null || true)
[[ -n "$tag" ]] || die 'Das Release-Skript hat keinen Git-Tag am neuen Release-Commit erzeugt.'

echo
echo "Release $tag erstellt."
echo "Nächste Schritte:"
echo "  git push origin main $tag"
echo "  ./planer prod backup create"
echo "  ./planer prod update --ref $tag --backup-confirmed"
