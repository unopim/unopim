#!/bin/bash
# Guarantees vendor/autoload.php exists before the first artisan call. The
# source stack bind-mounts the checkout over /var/www/html, which shadows the
# vendor directory baked into the image.

ensure_vendor() {
    local root="${1:-/var/www/html}"

    if [ -f "${root}/vendor/autoload.php" ] \
        && [ ! "${root}/composer.lock" -nt "${root}/vendor/composer/installed.json" ]; then
        return 0
    fi

    if ! command -v composer >/dev/null 2>&1; then
        echo "✗ vendor/autoload.php is missing and Composer is unavailable." >&2
        echo "  Run 'composer install' on the host, or use the published images." >&2
        return 1
    fi

    echo "→ Installing Composer dependencies..."

    # shellcheck disable=SC2086
    composer install \
        --working-dir="$root" \
        --no-interaction \
        --optimize-autoloader \
        ${COMPOSER_INSTALL_ARGS:---no-dev}
}
