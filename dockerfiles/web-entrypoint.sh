#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# First-time setup: install dependencies and run installer
if [ ! -f "$LOCK_FILE" ]; then
    echo "First-time setup: installing dependencies..."
    composer install --no-interaction --optimize-autoloader
    npm install
    npm run build
    php artisan unopim:install -n --skip-env-check --skip-admin-creation
    touch "$LOCK_FILE"
    echo "Setup complete."
fi

# Run pending migrations on subsequent starts
if [ -f "$LOCK_FILE" ]; then
    php artisan migrate --force --no-interaction 2>/dev/null || true
fi

# Ensure storage directories are writable
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Hand back control to Apache
exec apache2-foreground
