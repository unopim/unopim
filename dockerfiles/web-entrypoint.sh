#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

if [ ! -f "$LOCK_FILE" ]; then
    echo "Running first-time installation..."

    composer install
    npm install
    php artisan unopim:install -n

    touch "$LOCK_FILE"
    echo "Installation completed."
else
    echo "UnoPim already installed. Skipping install steps."
fi

chown -R 1001:1001 /var/www/html/storage

exec apache2-foreground
