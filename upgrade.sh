#!/bin/bash

set -e

# Read a single value from .env (first match, key anchored, quotes stripped)
env_val() {
  grep -E "^$1=" .env | head -n 1 | cut -d '=' -f 2- | tr -d '\r' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
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
    echo "⚠️ Failed to create database dump."
  fi

  # Keep user data (storage/app uploads, media) in the backup; exclude
  # dependencies, regenerable framework caches, logs and debugbar output.
  zip -rq $BACKUP_FILE . \
    -x "./vendor/*" \
    -x "./node_modules/*" \
    -x "./backups/*" \
    -x "./storage/framework/cache/*" \
    -x "./storage/framework/sessions/*" \
    -x "./storage/framework/testing/*" \
    -x "./storage/framework/views/*" \
    -x "./storage/debugbar/*" \
    -x "./storage/logs/*"

  zip -urq $BACKUP_FILE "storage/framework/cache/.gitignore" "storage/framework/sessions/.gitignore" "storage/framework/testing/.gitignore" "storage/framework/views/.gitignore" "storage/debugbar/.gitignore" "storage/logs/.gitignore"

  rm -f "$DB_DUMP_FILE"
}

# Function to create database dump (supports MySQL and PostgreSQL)
create_database_dump() {
  DB_CONNECTION=$(env_val DB_CONNECTION)
  DB_HOST=$(env_val DB_HOST)
  DB_PORT=$(env_val DB_PORT)
  DB_NAME=$(env_val DB_DATABASE)
  DB_USER=$(env_val DB_USERNAME)
  DB_PASS=$(env_val DB_PASSWORD)

  if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
    return 1
  fi

  case "$DB_CONNECTION" in
    pgsql)
      PGPASSWORD="$DB_PASS" pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" "$DB_NAME" > "$1"
      ;;
    mysql|"")
      MYSQL_PWD="$DB_PASS" mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" "$DB_NAME" > "$1"
      ;;
    *)
      echo "⚠️ Unsupported DB_CONNECTION: $DB_CONNECTION"
      return 1
      ;;
  esac
}

# Function to copy folders (including dotfiles like .env.example, .gitignore)
copy_folder() {
  SRC=$1
  DST=$2
  mkdir -p "$DST"

  shopt -s dotglob
  for ITEM in "$SRC"/*; do
    NAME=$(basename "$ITEM")
    case "$NAME" in
      .env|vendor|storage|backups) echo "Skipping $NAME"; continue ;;
    esac
    cp -a "$ITEM" "$DST/"
  done
  shopt -u dotglob
}

# Configuration
GITHUB_OWNER="unopim"
GITHUB_REPO="unopim"
BACKUP_DIR="./backups"
ROOT_PATH="$(pwd)"
CURRENT_VERSION=$(php artisan unopim:version)
echo -e "\n🔧 Starting Unopim upgrade script...\n"

# 1. Get current version
echo "📌 Current version: $CURRENT_VERSION"

UPGRADE_TO_VERSION="https://api.github.com/repos/$GITHUB_OWNER/$GITHUB_REPO/releases/latest"

RELEASE_INFO=$(curl -s -H "Accept: application/vnd.github.v3+json" "$UPGRADE_TO_VERSION")
if echo "$RELEASE_INFO" | grep -q "<html>"; then
  echo "❌ Received HTML response instead of JSON. Possible issue with the GitHub API request."
  exit 1
fi

if [[ -z "$RELEASE_INFO" ]]; then
  echo "❌ Failed to fetch release information."
  exit 1
fi
# Extract the version from the release information
UPGRADE_TO_VERSION=$(echo "$RELEASE_INFO" | grep -oP '"tag_name":\s*"\K(.*?)(?=")')
if [[ -z "$UPGRADE_TO_VERSION" ]]; then
  echo "❌ Failed to parse the version tag from release information."
  exit 1
fi

LATEST_VERSION="${UPGRADE_TO_VERSION//v/}"

echo "✅ Latest version: $LATEST_VERSION"

if [[ "$CURRENT_VERSION" == "$LATEST_VERSION" ]]; then
  echo "✅ Already up to date!"
  exit 0
fi

# 3. Create backup
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_PATH="$BACKUP_DIR/$CURRENT_VERSION-unopim-backup-$TIMESTAMP.zip"
mkdir -p "$BACKUP_DIR"
backup_project "$BACKUP_PATH" "$ROOT_PATH"
echo "✅ Backup created: $BACKUP_PATH"

# 4. Download latest release
ZIP_URL="https://github.com/$GITHUB_OWNER/$GITHUB_REPO/archive/refs/tags/$UPGRADE_TO_VERSION.zip"
ZIP_FILE="./$LATEST_VERSION-unopim-update.zip"
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

echo "🧹 Clearing cache..."
php artisan optimize:clear

echo "🛠️ Sending queue restart signal..."
php artisan queue:restart

echo "🔄 Clearing Elasticsearch indexes..."
php artisan unopim:elastic:clear

echo "📦 Re-indexing products..."
php artisan unopim:product:index

echo "📂 Re-indexing categories..."
php artisan unopim:category:index

echo "✅ Upgrade complete! Now on version $LATEST_VERSION"
