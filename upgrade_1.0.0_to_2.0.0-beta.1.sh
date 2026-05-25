#!/bin/bash

set -e

# ============================================================================
# UnoPim Upgrade Script: v1.0.0 -> v2.0.0-beta.1
# ============================================================================
# This script automates the upgrade process from UnoPim v1.0.0 to v2.0.0-beta.1.
# It will:
#   1. Check PHP version (requires 8.3+)
#   2. Backup your project and database
#   3. Download the v2.0.0-beta.1 release
#   4. Copy new files (preserving .env, storage, backups)
#   5. Remove files deleted in Laravel 12 upgrade
#   6. Install Composer dependencies
#   7. Run database migrations
#   8. Clear caches and link storage
#   9. Send queue restart signal
# ============================================================================

# Function to check PHP version
check_php_version() {
  REQUIRED_MAJOR=8
  REQUIRED_MINOR=3

  PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
  PHP_MAJOR=$(echo "$PHP_VERSION" | cut -d. -f1)
  PHP_MINOR=$(echo "$PHP_VERSION" | cut -d. -f2)

  if [[ "$PHP_MAJOR" -lt "$REQUIRED_MAJOR" ]] || { [[ "$PHP_MAJOR" -eq "$REQUIRED_MAJOR" ]] && [[ "$PHP_MINOR" -lt "$REQUIRED_MINOR" ]]; }; then
    echo "❌ PHP $REQUIRED_MAJOR.$REQUIRED_MINOR or higher is required. Current version: $PHP_VERSION"
    echo "   Please upgrade PHP before running this script."
    exit 1
  fi

  echo "✅ PHP version: $PHP_VERSION"
}

# Function to create project backup
backup_project() {
  BACKUP_FILE=$1
  ROOT_PATH=$2

  echo "Creating backup: $BACKUP_FILE"

  TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
  # Backup database
  DB_DUMP_FILE="./$TIMESTAMP-db-backup.sql"
  if create_database_dump "$DB_DUMP_FILE"; then
    echo "✅ Database backed up."
  else
    echo "⚠️  Failed to create database dump. Continuing without database backup."
  fi

  zip -rq "$BACKUP_FILE" . -x "./vendor/*" "./node_modules/*" "./storage/*" "./backups/*"

  zip -urq "$BACKUP_FILE" "./storage/app/public/data-transfer/samples/" \
    "storage/app/private/.gitignore" \
    "storage/fonts/.gitignore" \
    "storage/debugbar/.gitignore" \
    "storage/framework/cache/.gitignore" \
    "storage/framework/sessions/.gitignore" \
    "storage/framework/testing/.gitignore" \
    "storage/framework/views/.gitignore" \
    "storage/logs/.gitignore" 2>/dev/null || true

  if [[ -f "$DB_DUMP_FILE" ]]; then
    rm "$DB_DUMP_FILE"
  fi
}

# Function to create database dump
create_database_dump() {
  DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d '=' -f 2 | tr -d '[:space:]')
  DB_PORT=$(grep -E '^DB_PORT=' .env | cut -d '=' -f 2 | tr -d '[:space:]')
  DB_NAME=$(grep -E '^DB_DATABASE=' .env | cut -d '=' -f 2 | tr -d '[:space:]')
  DB_USER=$(grep -E '^DB_USERNAME=' .env | cut -d '=' -f 2 | tr -d '[:space:]')
  DB_PASS=$(grep -E '^DB_PASSWORD=' .env | cut -d '=' -f 2 | tr -d '[:space:]')

  if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
    return 1
  fi

  mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$1"
  return $?
}

# Function to copy folders
copy_folder() {
  SRC=$1
  DST=$2
  mkdir -p "$DST"

  for ITEM in "$SRC"/*; do
    NAME=$(basename "$ITEM")
    case "$NAME" in
      .env|vendor|storage|backups) echo "  Skipping $NAME"; continue ;;
    esac
    cp -a "$ITEM" "$DST/"
  done
}

# Function to remove files deleted in Laravel 12
remove_deleted_files() {
  echo "🗑️  Removing files replaced by Laravel 12 architecture..."

  FILES_TO_REMOVE=(
    "app/Console/Kernel.php"
    "app/Exceptions/Handler.php"
    "app/Http/Kernel.php"
    "app/Http/Middleware/EncryptCookies.php"
    "app/Http/Middleware/RedirectIfAuthenticated.php"
    "app/Http/Middleware/TrimStrings.php"
    "app/Http/Middleware/TrustHosts.php"
    "app/Http/Middleware/TrustProxies.php"
    "app/Http/Middleware/VerifyCsrfToken.php"
    "app/Providers/AuthServiceProvider.php"
    "app/Providers/BroadcastServiceProvider.php"
    "app/Providers/EventServiceProvider.php"
    "app/Providers/RouteServiceProvider.php"
  )

  for FILE in "${FILES_TO_REMOVE[@]}"; do
    if [[ -f "$FILE" ]]; then
      rm "$FILE"
      echo "  Removed: $FILE"
    fi
  done

  # Remove old MagicAI provider-specific service classes
  OLD_MAGIC_AI_SERVICES=(
    "packages/Webkul/MagicAI/src/Services/OpenAI.php"
    "packages/Webkul/MagicAI/src/Services/Gemini.php"
    "packages/Webkul/MagicAI/src/Services/Groq.php"
    "packages/Webkul/MagicAI/src/Services/Ollama.php"
  )

  for FILE in "${OLD_MAGIC_AI_SERVICES[@]}"; do
    if [[ -f "$FILE" ]]; then
      rm "$FILE"
      echo "  Removed: $FILE"
    fi
  done

  # Remove old ImageManager
  if [[ -f "packages/Webkul/Core/src/ImageCache/ImageManager.php" ]]; then
    rm "packages/Webkul/Core/src/ImageCache/ImageManager.php"
    echo "  Removed: packages/Webkul/Core/src/ImageCache/ImageManager.php"
  fi

  # Remove old build assets
  OLD_ASSETS=(
    "public/themes/admin/default/build/assets/app-3cada633.css"
    "public/themes/admin/default/build/assets/app-83621437.css"
    "public/themes/admin/default/build/assets/app-d463c1dd.js"
  )

  for FILE in "${OLD_ASSETS[@]}"; do
    if [[ -f "$FILE" ]]; then
      rm "$FILE"
      echo "  Removed: $FILE"
    fi
  done

  echo "✅ Old files removed."
}

# ============================================================================
# Main Script
# ============================================================================

# Configuration
GITHUB_OWNER="unopim"
GITHUB_REPO="unopim"
BACKUP_DIR="./backups"
ROOT_PATH="$(pwd)"
CURRENT_VERSION=$(php artisan unopim:version 2>/dev/null || echo "unknown")
UPGRADE_TO_VERSION_TAG="v2.0.0-beta.1"

echo ""
echo "============================================"
echo "  UnoPim Upgrade: v1.0.0 -> v2.0.0-beta.1"
echo "============================================"
echo ""

# 1. Check PHP version
echo "📋 Checking prerequisites..."
check_php_version

# 2. Get current version
echo "📌 Current version: $CURRENT_VERSION"

UPGRADE_VERSION="${UPGRADE_TO_VERSION_TAG//v/}"
CURRENT_VERSION_CLEAN="${CURRENT_VERSION//v/}"

if [[ "$CURRENT_VERSION_CLEAN" != "unknown" ]]; then
  if [[ "$(echo -e "$CURRENT_VERSION_CLEAN\n$UPGRADE_VERSION" | sort -V | head -n 1)" == "$UPGRADE_VERSION" ]]; then
    echo "✅ Already up to date (version $CURRENT_VERSION_CLEAN >= $UPGRADE_VERSION)!"
    exit 0
  fi
fi

echo "✅ Upgrading to version: $UPGRADE_TO_VERSION_TAG"

# 3. Create backup
echo ""
echo "📦 Creating backup..."
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_PATH="$BACKUP_DIR/$CURRENT_VERSION_CLEAN-unopim-backup-$TIMESTAMP.zip"
mkdir -p "$BACKUP_DIR"
backup_project "$BACKUP_PATH" "$ROOT_PATH"
echo "✅ Backup created: $BACKUP_PATH"

# 4. Download latest release
ZIP_URL="https://github.com/$GITHUB_OWNER/$GITHUB_REPO/archive/refs/tags/$UPGRADE_TO_VERSION_TAG.zip"
ZIP_FILE="./$UPGRADE_VERSION-unopim-update.zip"
echo ""
echo "⬇️  Downloading release $UPGRADE_TO_VERSION_TAG from GitHub..."

curl -fL -o "$ZIP_FILE" "$ZIP_URL"

echo "✅ Downloaded to: $ZIP_FILE"

if ! unzip -t "$ZIP_FILE" > /dev/null 2>&1; then
  echo "❌ Downloaded file is not a valid ZIP archive."
  rm -f "$ZIP_FILE"
  exit 1
fi

# 5. Extract release
TEMP_DIR="./.upgrade_temp"
mkdir -p "$TEMP_DIR"
echo ""
echo "📦 Extracting release..."
unzip -q "$ZIP_FILE" -d "$TEMP_DIR"
rm -f "$ZIP_FILE"

# 6. Copy new files (excluding .env, storage, backups, vendor)
EXTRACTED_FOLDER=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -n 1)
echo "📁 Copying new files into project..."
copy_folder "$EXTRACTED_FOLDER" "$ROOT_PATH"

# 7. Cleanup temp
rm -rf "$TEMP_DIR"

# 8. Remove files that were deleted in the Laravel 12 upgrade
remove_deleted_files

# 9. Run Composer install
echo ""
echo "📦 Running composer install..."
composer install --no-interaction

# 10. Run migrations
echo ""
echo "🛠️  Running database migrations..."
php artisan migrate --force

# 11. Link storage
echo "🔗 Linking storage..."
php artisan storage:link 2>/dev/null || true

# 12. Send queue restart signal
echo "🛠️  Sending queue restart signal..."
php artisan queue:restart

# 13. Clear cache
echo "🧹 Clearing cache..."
php artisan optimize:clear

# 14. Done
echo ""
echo "============================================"
echo "  ✅ Upgrade complete!"
echo "  UnoPim is now on version $UPGRADE_VERSION"
echo "============================================"
echo ""
echo "📋 Post-upgrade steps:"
echo "   1. Restart your queue worker / Supervisor"
echo "      sudo supervisorctl restart unopim-worker"
echo ""
echo "   2. If you use Elasticsearch, rebuild indexes:"
echo "      php artisan unopim:elastic:clear"
echo "      php artisan unopim:product:index"
echo "      php artisan unopim:category:index"
echo ""
echo "   3. If you had custom middleware, providers, or scheduled"
echo "      commands, migrate them to the new Laravel 12 patterns."
echo "      See UPGRADE-1.0.0-2.0.0.md for details."
echo ""
echo "   4. Test your application thoroughly."
echo ""
