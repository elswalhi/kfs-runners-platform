#!/usr/bin/env bash
set -e

echo "🚀 [Deploy] Starting deployment on Hostinger..."

# Locate Hostinger domain folder (kfsmarathon.elatiky.com or runners.elatiky.com)
TARGET_DOMAIN="kfsmarathon.elatiky.com"
if [ ! -d "$HOME/domains/$TARGET_DOMAIN" ]; then
  if [ -d "$HOME/domains/runners.elatiky.com" ]; then
    TARGET_DOMAIN="runners.elatiky.com"
  fi
fi

APP_DIR="$HOME/domains/$TARGET_DOMAIN/kfs-app"
WEB_DIR="$HOME/domains/$TARGET_DOMAIN/public_html"

echo "🎯 [Deploy] Target Domain: $TARGET_DOMAIN"
echo "📁 [Deploy] App directory: $APP_DIR"
echo "🌐 [Deploy] Web directory: $WEB_DIR"

# Ensure directories exist
mkdir -p "$APP_DIR"
mkdir -p "$WEB_DIR"

# Sync Laravel Core into kfs-app
echo "📦 [Deploy] Syncing Laravel backend core..."
cp -rf backend/app "$APP_DIR/"
cp -rf backend/config "$APP_DIR/"
cp -rf backend/database "$APP_DIR/"
cp -rf backend/lang "$APP_DIR/"
cp -rf backend/resources "$APP_DIR/"
cp -rf backend/routes "$APP_DIR/"
cp -f backend/artisan "$APP_DIR/"
cp -f backend/composer.json "$APP_DIR/"

# Sync public assets into public_html
echo "🎨 [Deploy] Syncing public assets..."
mkdir -p "$WEB_DIR/css" "$WEB_DIR/js"
cp -rf backend/public/css/* "$WEB_DIR/css/"
cp -rf backend/public/js/* "$WEB_DIR/js/"
if [ -d "backend/public/images" ]; then
  mkdir -p "$WEB_DIR/images"
  cp -rf backend/public/images/* "$WEB_DIR/images/"
fi

# Ensure web bridge index.php
if [ -f "deploy/hostinger/laravel-index.php" ]; then
  cp -f deploy/hostinger/laravel-index.php "$WEB_DIR/index.php"
fi

# Locate PHP 8.3 CLI
PHP_BIN="php"
if [ -x "/opt/alt/php83/usr/bin/php" ]; then
  PHP_BIN="/opt/alt/php83/usr/bin/php"
elif [ -x "/usr/bin/php8.3" ]; then
  PHP_BIN="/usr/bin/php8.3"
fi

echo "⚙️ [Deploy] Using PHP binary: $PHP_BIN"

# Clear and optimize Laravel caches
echo "🧹 [Deploy] Refreshing Laravel view & config caches..."
cd "$APP_DIR"
"$PHP_BIN" artisan view:clear || true
"$PHP_BIN" artisan config:clear || true
"$PHP_BIN" artisan route:clear || true
"$PHP_BIN" artisan cache:clear || true
"$PHP_BIN" artisan view:cache || true

# Purge LiteSpeed web server cache
touch "$WEB_DIR/.htaccess"

echo "✅ [Deploy] Deployment completed successfully for $TARGET_DOMAIN!"
