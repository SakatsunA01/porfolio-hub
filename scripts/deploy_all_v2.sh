#!/usr/bin/env bash
set -Eeuo pipefail

# -----------------------------
# Deploy all apps for Hostinger
# Safer version with rollback
# -----------------------------

USER_HOME="${USER_HOME:-/home/u863655389}"
DOMAIN_ROOT="${DOMAIN_ROOT:-$USER_HOME/domains/labarcaministerio.com/public_html}"
REPO_DIR="${REPO_DIR:-$USER_HOME/repos/porfolio-hub}"
REPO_URL="${REPO_URL:-https://github.com/SakatsunA01/porfolio-hub.git}"
BRANCH="${BRANCH:-main}"
SKIP_BUILD="${SKIP_BUILD:-0}" # set SKIP_BUILD=1 to skip npm build on server

LOCK_FILE="$USER_HOME/deploy_all_v2.lock"
TS="$(date +%F_%H-%M-%S)"
BACKUP_ROOT="$USER_HOME/deploy_backups/$TS"
LOG_FILE="$USER_HOME/deploy_all_v2_$TS.log"

API_ECOM="$DOMAIN_ROOT/apiecommerce"
API_DELIVERY="$DOMAIN_ROOT/apidelivery"
API_DUNAMIS="$DOMAIN_ROOT/apidunamis"
SHOP_WEB="$DOMAIN_ROOT/shop"
DELIVERY_WEB="$DOMAIN_ROOT/delivery"
DUNAMIS_WEB="$DOMAIN_ROOT/dunamis"
PORTFOLIO_WEB="$DOMAIN_ROOT/portfolio"

BACKENDS=("$API_ECOM" "$API_DELIVERY" "$API_DUNAMIS")
FRONTS=("$SHOP_WEB" "$DELIVERY_WEB" "$DUNAMIS_WEB" "$PORTFOLIO_WEB")

CURRENT_STAGE="init"
FAILED=0

log() {
  printf "[%s] %s\n" "$(date +%H:%M:%S)" "$*" | tee -a "$LOG_FILE"
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || { log "ERROR: missing command: $1"; exit 1; }
}

ensure_backend_bridge_files() {
  local app_dir="$1"

  cat > "$app_dir/index.php" <<'PHP'
<?php
require __DIR__.'/public/index.php';
PHP

  cat > "$app_dir/.htaccess" <<'HT'
<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    RewriteRule ^ index.php [L]
</IfModule>
HT
}

ensure_front_spa_htaccess() {
  local app_dir="$1"

  cat > "$app_dir/.htaccess" <<'HT'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    RewriteRule . /index.html [L]
</IfModule>
HT
}

backup_path() {
  local src="$1"
  local dst="$2"
  mkdir -p "$(dirname "$dst")"
  if [[ -d "$src" ]]; then
    rsync -a "$src"/ "$dst"/
  fi
}

restore_path() {
  local backup="$1"
  local dst="$2"
  if [[ -d "$backup" ]]; then
    mkdir -p "$dst"
    rsync -a --delete "$backup"/ "$dst"/
  fi
}

rollback() {
  log "Rollback iniciado por error en etapa: $CURRENT_STAGE"
  for app in "${BACKENDS[@]}"; do
    restore_path "$BACKUP_ROOT$app" "$app"
  done
  for app in "${FRONTS[@]}"; do
    restore_path "$BACKUP_ROOT$app" "$app"
  done
  log "Rollback completado."
}

on_error() {
  FAILED=1
  rollback
  log "Deploy fallido. Revisa log: $LOG_FILE"
  rm -f "$LOCK_FILE"
  exit 1
}

trap on_error ERR

if [[ -f "$LOCK_FILE" ]]; then
  echo "Deploy en curso detectado: $LOCK_FILE"
  exit 1
fi
touch "$LOCK_FILE"

require_cmd git
require_cmd rsync
require_cmd curl
require_cmd composer
require_cmd php

log "Backup inicial en: $BACKUP_ROOT"
for app in "${BACKENDS[@]}"; do
  backup_path "$app" "$BACKUP_ROOT$app"
done
for app in "${FRONTS[@]}"; do
  backup_path "$app" "$BACKUP_ROOT$app"
done

CURRENT_STAGE="prepare-repo"
log "Preparando repo..."
mkdir -p "$USER_HOME/repos"
if [[ ! -d "$REPO_DIR/.git" ]]; then
  git clone "$REPO_URL" "$REPO_DIR"
fi
cd "$REPO_DIR"
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"

build_front() {
  local app_dir="$1"
  if [[ "$SKIP_BUILD" == "1" ]]; then
    log "SKIP_BUILD=1, omitiendo build: $app_dir"
    return 0
  fi
  if [[ -f "$app_dir/package.json" ]]; then
    require_cmd npm
    log "Build frontend: $app_dir"
    (cd "$app_dir" && npm ci && npm run build)
  fi
}

deploy_laravel() {
  local src="$1"
  local dst="$2"

  CURRENT_STAGE="sync-backend-$dst"
  log "Sync backend: $src -> $dst"
  mkdir -p "$dst"

  rsync -av --delete \
    --exclude ".env" \
    --exclude "vendor" \
    --exclude "node_modules" \
    --exclude "index.php" \
    --exclude ".htaccess" \
    --exclude "storage/logs/*" \
    --exclude "storage/framework/cache/*" \
    --exclude "storage/framework/sessions/*" \
    --exclude "storage/framework/views/*" \
    "$src"/ "$dst"/

  ensure_backend_bridge_files "$dst"

  CURRENT_STAGE="artisan-$dst"
  cd "$dst"
  composer install --no-dev --optimize-autoloader
  php artisan optimize:clear
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache || true
  php artisan storage:link || true
}

deploy_front() {
  local src_dist="$1"
  local dst="$2"
  CURRENT_STAGE="sync-front-$dst"
  log "Sync frontend: $src_dist -> $dst"
  mkdir -p "$dst"
  rsync -av --delete "$src_dist"/ "$dst"/
  ensure_front_spa_htaccess "$dst"
}

# Build steps
build_front "$REPO_DIR/apps-demos/ecommerce/sak-commerce"
build_front "$REPO_DIR/apps-demos/delivery-app/frontend"
build_front "$REPO_DIR/apps-demos/dunamis-saas/frontend"
build_front "$REPO_DIR/portfolio-home"

# Backends
deploy_laravel "$REPO_DIR/apps-demos/ecommerce/commerce-back" "$API_ECOM"
deploy_laravel "$REPO_DIR/apps-demos/delivery-app/backend" "$API_DELIVERY"
deploy_laravel "$REPO_DIR/apps-demos/dunamis-saas/backend" "$API_DUNAMIS"

# Frontends
deploy_front "$REPO_DIR/apps-demos/ecommerce/sak-commerce/dist" "$SHOP_WEB"
deploy_front "$REPO_DIR/apps-demos/delivery-app/frontend/dist" "$DELIVERY_WEB"
deploy_front "$REPO_DIR/apps-demos/dunamis-saas/frontend/dist" "$DUNAMIS_WEB"
deploy_front "$REPO_DIR/portfolio-home/dist" "$PORTFOLIO_WEB"

CURRENT_STAGE="smoke-tests"
log "Ejecutando smoke tests..."

test_url() {
  local url="$1"
  local expected="$2"
  local code
  code="$(curl -s -o /dev/null -w "%{http_code}" "$url")"
  if [[ "$code" != "$expected" ]]; then
    log "ERROR smoke: $url -> $code (esperado $expected)"
    exit 1
  fi
  log "OK smoke: $url -> $code"
}

test_url "https://apiecommerce.labarcaministerio.com/sanctum/csrf-cookie" "204"
test_url "https://apiecommerce.labarcaministerio.com/api/store/products" "200"
test_url "https://apiecommerce.labarcaministerio.com/api/store/settings" "200"
test_url "https://shop.labarcaministerio.com/" "200"
test_url "https://shop.labarcaministerio.com/catalog" "200"

rm -f "$LOCK_FILE"
log "Deploy completado correctamente."
log "Log: $LOG_FILE"

