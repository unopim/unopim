#!/bin/bash

set -e

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
    echo "⚠️ Failed to create database dump."
  fi

  zip -rq $BACKUP_FILE . -x "./vendor/*" "./node_modules/*" "./storage/*" "./backups/*"

  zip -urq $BACKUP_FILE "./storage/app/public/data-transfer/samples/" "storage/app/private/.gitignore" "storage/fonts/.gitignore" "storage/debugbar/.gitignore" "storage/framework/cache/.gitignore" "storage/framework/sessions/.gitignore" "storage/framework/testing/.gitignore" "storage/framework/views/.gitignore" "storage/logs/.gitignore"

  rm $DB_DUMP_FILE
}

# Function to create database dump
create_database_dump() {
  DB_HOST=$(grep -E 'DB_HOST' .env | cut -d '=' -f 2 | tr -d '[:space:]')
  DB_PORT=$(grep -E 'DB_PORT' .env | cut -d '=' -f 2 | tr -d '[:space:]')
  DB_NAME=$(grep -E 'DB_DATABASE' .env | cut -d '=' -f 2 | tr -d '[:space:]')
  DB_USER=$(grep -E 'DB_USERNAME' .env | cut -d '=' -f 2 | tr -d '[:space:]')
  DB_PASS=$(grep -E 'DB_PASSWORD' .env | cut -d '=' -f 2 | tr -d '[:space:]')

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
      .env|vendor|storage|backups) echo "Skipping $NAME"; continue ;;
    esac
    cp -a "$ITEM" "$DST/"
  done
}

# Configuration
GITHUB_OWNER="unopim"
GITHUB_REPO="unopim"
BACKUP_DIR="./backups"
ROOT_PATH="$(pwd)"
CURRENT_VERSION=$(php artisan unopim:version)
UPGRADE_TO_VERSION_TAG="v1.0.0"
echo -e "\n🔧 Starting Unopim upgrade script...\n"

# 1. Get current version
echo "📌 Current version: $CURRENT_VERSION"

UPGRADE_VERSION="${UPGRADE_TO_VERSION_TAG//v/}"
CURRENT_VERSION="${CURRENT_VERSION//v/}"

if [[ "$(echo -e "$CURRENT_VERSION\n$UPGRADE_VERSION" | sort -V | head -n 1)" == "$UPGRADE_VERSION" ]]; then
  echo "✅ Already up to date!"
  exit 0
fi

echo "✅ Upgrading to version: $UPGRADE_TO_VERSION_TAG"
# 3. Create backup
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_PATH="$BACKUP_DIR/$CURRENT_VERSION-unopim-backup-$TIMESTAMP.zip"
mkdir -p "$BACKUP_DIR"
backup_project "$BACKUP_PATH" "$ROOT_PATH"
echo "✅ Backup created: $BACKUP_PATH"

# 4. Download latest release
ZIP_URL="https://github.com/$GITHUB_OWNER/$GITHUB_REPO/archive/refs/tags/$UPGRADE_TO_VERSION_TAG.zip"
ZIP_FILE="./$UPGRADE_VERSION-unopim-update.zip"
echo "⬇️  Downloading latest release from GitHub..."

curl -fL -o $ZIP_FILE $ZIP_URL

echo "✅ Downloaded to: $ZIP_FILE"

if ! unzip -t "$ZIP_FILE" > /dev/null 2>&1; then
  echo "❌ Downloaded file is not a valid ZIP archive."
  rm -f "$ZIP_FILE"
  exit 1
fi

# 5. Extract and overwrite core files (preserve user config)
TEMP_DIR="./.upgrade_temp"
mkdir -p "$TEMP_DIR"
echo "📦 Extracting release..."
unzip -q "$ZIP_FILE" -d "$TEMP_DIR"
rm -f "$ZIP_FILE"

# 6. Copy new files (excluding .env, storage, backups, vendor, etc.)
EXTRACTED_FOLDER=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -n 1)
echo "📁 Copying new files into project..."
copy_folder "$EXTRACTED_FOLDER" "$ROOT_PATH"

# 7. Cleanup temp
rm -rf "$TEMP_DIR"

# 8. Run Composer install & Laravel commands
echo "📦 Running composer install..."
composer install --no-interaction

echo "🛠️ Running migrations..."
php artisan migrate

echo "🔗 Linking storage..."
php artisan storage:link

echo "🛠️ Sending queue restart signal..."
php artisan queue:restart

echo "🧹 Clearing cache..."
php artisan optimize:clear

echo "✅ Upgrade complete! Now on version $UPGRADE_VERSION"
