#!/usr/bin/env bash
# Bezpieczny git pull na VPS — nie nadpisuje config/local.php ani config/database.php.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${ROOT}/storage/backups/deploy-${STAMP}"
mkdir -p "$BACKUP_DIR"

backup_if_exists() {
    local f="$1"
    if [[ -f "$f" ]]; then
        cp -a "$f" "${BACKUP_DIR}/$(basename "$f")"
        echo "→ kopia: $f → ${BACKUP_DIR}/$(basename "$f")"
    fi
}

echo "==> Kopie zapasowe (local.php, database.php)"
backup_if_exists config/local.php
backup_if_exists config/database.php

echo "==> Cofam lokalne zmiany w plikach z repozytorium (app.php, .htaccess…)"
git restore config/app.php public/assets/uploads/.htaccess 2>/dev/null || \
    git checkout -- config/app.php public/assets/uploads/.htaccess 2>/dev/null || true

# database.php bywa zmieniany na serwerze — schowaj przed pull (plik jest w .gitignore)
if git ls-files --error-unmatch config/database.php >/dev/null 2>&1; then
    if ! git diff --quiet config/database.php 2>/dev/null; then
        echo "==> Cofam śledzoną wersję config/database.php (zostanie przywrócona z kopii)"
        git restore config/database.php 2>/dev/null || git checkout -- config/database.php
    fi
fi

echo "==> git pull"
git pull origin main

# Po pull database.php może nie istnieć (usunięty z repo) — odtwórz z kopii lub example
if [[ ! -f config/database.php ]]; then
    if [[ -f "${BACKUP_DIR}/database.php" ]]; then
        cp -a "${BACKUP_DIR}/database.php" config/database.php
        echo "→ przywrócono config/database.php z kopii"
    elif [[ -f config/database.php.example ]]; then
        cp config/database.php.example config/database.php
        echo "→ utworzono config/database.php z database.php.example"
    fi
fi

if [[ ! -f config/local.php ]]; then
    echo ""
    echo "UWAGA: brak config/local.php — utwórz z local.php.example (hasło DB, sekrety, https://)"
    echo "  cp config/local.php.example config/local.php && nano config/local.php"
fi

echo ""
echo "OK. Aktualna wersja: $(git log -1 --oneline)"
echo "Kopie: ${BACKUP_DIR}"
