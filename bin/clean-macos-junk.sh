#!/usr/bin/env bash
# Usuwa pliki ._ (AppleDouble) i .DS_Store tworzone przez macOS na exFAT/sieciowych dyskach.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if command -v dot_clean >/dev/null 2>&1; then
    dot_clean -m .
else
    find . \( -path './.git' -o -path './.git/*' \) -prune -o -name '._*' -type f -print -delete
    find . \( -path './.git' -o -path './.git/*' \) -prune -o -name '._*' -type d -empty -print -delete
fi

find . \( -path './.git' -o -path './.git/*' \) -prune -o -name '.DS_Store' -type f -print -delete

echo "Usunięto śmieci macOS z: $ROOT"
