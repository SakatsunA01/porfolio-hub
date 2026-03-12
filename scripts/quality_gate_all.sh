#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

run_front() {
  local dir="$1"
  local install_cmd="$2"
  local checks="$3"

  echo "[front] $dir"
  (
    cd "$ROOT_DIR/$dir"
    eval "$install_cmd"
    eval "$checks"
  )
}

run_back() {
  local dir="$1"

  echo "[back] $dir"
  (
    cd "$ROOT_DIR/$dir"
    composer install --no-interaction --prefer-dist
    php artisan test
  )
}

run_front "portfolio-home" "npm ci" "npm run build"
run_front "apps-demos/ecommerce/sak-commerce" "npm ci" "npm run type-check && npm run build"
run_front "apps-demos/dunamis-saas/frontend" "npm ci" "npm run build"
run_front "apps-demos/delivery-app/frontend" "npm ci" "npm run type-check && npm run lint && npm run build"

run_back "apps-demos/ecommerce/commerce-back"
run_back "apps-demos/dunamis-saas/backend"
run_back "apps-demos/delivery-app/backend"

echo "All quality gates passed."
