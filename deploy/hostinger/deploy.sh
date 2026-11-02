#!/usr/bin/env bash
set -e

echo "🚀 [Deploy] Starting deployment on Hostinger..."

# Prefer the current public URL, keeping the previous configured app as a fallback.
TARGET_DOMAIN=""
for CANDIDATE_DOMAIN in runners.elatiky.com kfsmarathon.elatiky.com; do
  CANDIDATE_APP="$HOME/domains/$CANDIDATE_DOMAIN/kfs-app"
  if [ -f "$CANDIDATE_APP/.env" ] && [ -d "$CANDIDATE_APP/vendor" ]; then
    TARGET_DOMAIN="$CANDIDATE_DOMAIN"
    break
  fi
done

if [ -z "$TARGET_DOMAIN" ]; then
  echo "❌ [Deploy] No configured Laravel app found under ~/domains/runners.elatiky.com or ~/domains/kfsmarathon.elatiky.com."
  exit 1
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

# Apply schema changes and add the bundled historical event pages. Avoid running
# the full DatabaseSeeder here because it also synchronizes the administrator account.
echo "🗄️ [Deploy] Applying database migrations..."
"$PHP_BIN" artisan migrate --force
echo "📰 [Deploy] Seeding historical event pages..."
"$PHP_BIN" artisan db:seed --class="Database\\Seeders\\HistoricalEventsSeeder" --force

"$PHP_BIN" artisan view:cache || true

# Purge LiteSpeed web server cache
touch "$WEB_DIR/.htaccess"

echo "✅ [Deploy] Deployment completed successfully for $TARGET_DOMAIN!"
