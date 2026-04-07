#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# First-time setup: install dependencies, migrate, and seed
if [ ! -f "$LOCK_FILE" ]; then
    echo "First-time setup: installing dependencies..."
    composer install --no-interaction --optimize-autoloader

    # Generate app key if not set
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
        php artisan key:generate --force
    fi

    php artisan migrate --force
    php artisan db:seed --force
    touch "$LOCK_FILE"
    echo "Setup complete."
else
    # Run pending migrations on subsequent starts
    php artisan migrate --force --no-interaction 2>/dev/null || true
fi

# Ensure storage directories are writable
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Hand back control to Apache
exec apache2-foreground
