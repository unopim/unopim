#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# ─── First-time setup ───────────────────────────────────────────────
if [ ! -f "$LOCK_FILE" ]; then
    echo "══════════════════════════════════════════════"
    echo "  UnoPim: First-time setup"
    echo "══════════════════════════════════════════════"

    echo "→ Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader --no-dev

    # Generate APP_KEY if not already set in .env
    if grep -q "^APP_KEY=$" /var/www/html/.env 2>/dev/null || [ -z "$APP_KEY" ]; then
        echo "→ Generating application key..."
        php artisan key:generate --force
    fi

    echo "→ Running database migrations..."
    php artisan migrate --force

    echo "→ Seeding database..."
    php artisan db:seed --force

    echo "→ Linking storage..."
    php artisan storage:link 2>/dev/null || true

    touch "$LOCK_FILE"

    echo "══════════════════════════════════════════════"
    echo "  Setup complete! Open http://localhost:${APP_PORT:-8000}/admin"
    echo "══════════════════════════════════════════════"
else
    # Run pending migrations on subsequent starts (safe — never drops tables)
    echo "→ Checking for pending migrations..."
    php artisan migrate --force --no-interaction 2>/dev/null || true
fi

# Ensure storage directories are writable by the web server
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Clear compiled views (may be stale from previous container)
php artisan view:clear 2>/dev/null || true

exec apache2-foreground
