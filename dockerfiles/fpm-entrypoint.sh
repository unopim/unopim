#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# ─── First-time setup ───────────────────────────────────────────────
if [ ! -f "$LOCK_FILE" ]; then

    # Check if database already has data (lock file deleted but DB intact)
    DB_INITIALIZED=0
    if php artisan migrate:status --no-interaction >/dev/null 2>&1; then
        DB_INITIALIZED=1
    fi

    if [ "$DB_INITIALIZED" -eq 1 ]; then
        echo "→ Existing database detected. Recreating lock file..."
        echo "→ Checking for pending migrations..."
        php artisan migrate --force --no-interaction 2>/dev/null || true
        echo "→ Linking storage..."
        php artisan storage:link 2>/dev/null || true
        touch "$LOCK_FILE"
    else
        echo "══════════════════════════════════════════════"
        echo "  UnoPim: First-time setup"
        echo "══════════════════════════════════════════════"

        echo "→ Installing Composer dependencies..."
        composer install --no-interaction --optimize-autoloader --no-dev

        # Generate APP_KEY if not already set in .env
        if grep -q "^APP_KEY=$" /var/www/html/.env 2>/dev/null || [ -z "$APP_KEY" ]; then
            echo "→ Generating application key..."
            php artisan key:generate --force
            # Export into current process so PHP-FPM inherits the new key
            export APP_KEY=$(grep "^APP_KEY=" /var/www/html/.env | cut -d '=' -f 2-)
        fi

        echo "→ Running database migrations..."
        php artisan migrate --force

        echo "→ Seeding database..."
        php artisan db:seed --force

        echo "→ Linking storage..."
        php artisan storage:link 2>/dev/null || true

        # Build Elasticsearch indexes if enabled
        if [ "${ELASTICSEARCH_ENABLED:-false}" = "true" ]; then
            echo "→ Building Elasticsearch indexes..."
            php artisan unopim:product:index --no-interaction 2>/dev/null || true
            php artisan unopim:category:index --no-interaction 2>/dev/null || true
        fi

        touch "$LOCK_FILE"

        echo "══════════════════════════════════════════════"
        echo "  Setup complete! Open http://localhost:${APP_PORT:-8000}/admin"
        echo "══════════════════════════════════════════════"
    fi
else
    # Run pending migrations on subsequent starts (safe — never drops tables)
    echo "→ Checking for pending migrations..."
    php artisan migrate --force --no-interaction
fi

# Ensure storage directories are writable
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Clear compiled views (may be stale from previous container)
php artisan view:clear 2>/dev/null || true

# Start PHP-FPM (master runs as root, workers run as www-data via www.conf)
exec php-fpm
