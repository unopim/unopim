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

# System dependencies + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    unzip \
    gosu \
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

# Bracket trick `[a]rtisan` keeps pgrep from matching its own command line.
# Matches both queue:work (default) and schedule:work (scheduler service uses
# this same image with an overridden entrypoint).
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD pgrep -f "[a]rtisan (queue|schedule):work" >/dev/null || exit 1

ENTRYPOINT ["/var/www/html/dockerfiles/q-entrypoint.sh"]
