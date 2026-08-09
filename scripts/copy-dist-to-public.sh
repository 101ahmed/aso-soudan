#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/frontend/dist"
PUBLIC="$ROOT/backend/public"

if [[ ! -d "$DIST" ]]; then
  echo "Missing frontend/dist. Run: cd frontend && npm run build" >&2
  exit 1
fi

rm -rf "$PUBLIC/assets"
# Copy SPA files without clobbering Laravel entrypoints
shopt -s dotglob nullglob
for entry in "$DIST"/*; do
  name="$(basename "$entry")"
  case "$name" in
    .htaccess|index.php|robots.txt|favicon.ico)
      echo "Skip preserved name from dist: $name"
      continue
      ;;
  esac
  rm -rf "$PUBLIC/$name"
  cp -R "$entry" "$PUBLIC/$name"
  echo "Copied $name"
done

if [[ ! -f "$PUBLIC/index.html" ]]; then
  echo "Copy failed: backend/public/index.html missing" >&2
  exit 1
fi

echo "SPA build copied to backend/public (index.php / .htaccess preserved)."
