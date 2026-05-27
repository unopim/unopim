# =============================================================================
# UnoPim Queue Worker (PHP 8.3 CLI)
# =============================================================================
# Lightweight CLI image for processing background jobs.
# Processes webhooks, system, completeness, and default queues.
#
# Multi-stage build:
#   Stage 1 (composer) — install PHP dependencies
#   Stage 2 (app)      — production CLI image
# =============================================================================

# ---------------------------------------------------------------------------
# Stage 1: Composer dependencies
# ---------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
COPY packages/ packages/
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Stage 2: Production image
# ---------------------------------------------------------------------------
FROM php:8.3-cli

LABEL maintainer="Webkul <support@webkul.com>"
LABEL org.opencontainers.image.title="UnoPim Queue Worker"
LABEL org.opencontainers.image.description="Background job processor for UnoPim PIM"
LABEL org.opencontainers.image.source="https://github.com/unopim/unopim"

# System dependencies + PHP extensions.
# procps provides pgrep, used by the HEALTHCHECK below.
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    unzip \
    gosu \
    procps \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    libgmp-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        calendar \
        exif \
        gd \
        gmp \
        intl \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP production configuration
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY dockerfiles/php.ini "$PHP_INI_DIR/conf.d/unopim.ini"

# Application code + Composer vendor from stage 1
WORKDIR /var/www/html
COPY . .
COPY --from=composer /app/vendor ./vendor

# Healthcheck logic:
#   1. If the install lock file does not exist yet, the worker is still
#      waiting for the web/fpm container to finish first-time setup
#      (q-entrypoint.sh blocks on this for up to SETUP_WAIT_TIMEOUT
#      seconds, which is operator-overridable). Report healthy so the
#      orchestrator does not restart-loop while setup runs.
#   2. Once the lock exists, require an active queue:work or
#      schedule:work process. The bracket trick `[a]rtisan` prevents
#      pgrep from matching its own command line.
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD [ ! -f /var/www/html/storage/unopim.lock ] \
        || pgrep -f "[a]rtisan (queue|schedule):work" >/dev/null \
        || exit 1

ENTRYPOINT ["/var/www/html/dockerfiles/q-entrypoint.sh"]
