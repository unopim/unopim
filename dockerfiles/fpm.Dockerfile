# =============================================================================
# UnoPim PHP-FPM Application Server
# =============================================================================
# Multi-stage build:
#   Stage 1 (composer) — install PHP dependencies
#   Stage 2 (app)      — production PHP-FPM image
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
FROM php:8.4-fpm

LABEL maintainer="Webkul <support@webkul.com>"
LABEL org.opencontainers.image.title="UnoPim FPM"
LABEL org.opencontainers.image.description="PHP-FPM application server for UnoPim PIM"
LABEL org.opencontainers.image.source="https://github.com/unopim/unopim"

# System dependencies + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    libgmp-dev \
    libpq-dev \
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
        pdo_pgsql \
        pgsql \
        zip \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Coverage driver for Pest's Test Impact Analysis. Off by default so production
# images stay free of it; enable with --build-arg INSTALL_COVERAGE_EXTENSION=true.
# Even when installed it stays dormant (pcov.enabled=0) until a test run turns it on.
ARG INSTALL_COVERAGE_EXTENSION=false
RUN if [ "$INSTALL_COVERAGE_EXTENSION" = "true" ]; then \
        pecl install pcov-1.0.12 \
        && docker-php-ext-enable pcov \
        && echo 'pcov.enabled=0' >> "$PHP_INI_DIR/conf.d/docker-php-ext-pcov.ini"; \
    fi

# PHP production configuration
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY dockerfiles/php.ini "$PHP_INI_DIR/conf.d/unopim.ini"

# PHP-FPM pool configuration
COPY dockerfiles/www.conf /usr/local/etc/php-fpm.d/www.conf

# Install Composer (for runtime use in entrypoint)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Application code + Composer vendor from stage 1
WORKDIR /var/www/html
COPY . .
COPY --from=composer /app/vendor ./vendor

RUN touch vendor/composer/installed.json

# Align www-data with the host user so bind-mounted storage stays
# writable on both sides (defaults keep the stock image behavior).
ARG HOST_UID=33
ARG HOST_GID=33
RUN groupmod -o -g "${HOST_GID}" www-data \
    && usermod -o -u "${HOST_UID}" -g "${HOST_GID}" www-data

# Set permissions. /var/www is www-data's home, so it has to be writable for
# tooling that caches there (Pest's Test Impact Analysis graph, Composer).
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown www-data:www-data /var/www

# The bind-mounted tree is owned by the host user, which Git refuses to read
# from another uid. Test Impact Analysis shells out to Git to find changed files.
RUN git config --system --add safe.directory /var/www/html

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=10s --start-period=180s --retries=3 \
    CMD test -f /var/www/html/storage/unopim.lock \
        && timeout 5 bash -c 'exec 3<>/dev/tcp/127.0.0.1/9000'

ENTRYPOINT ["/var/www/html/dockerfiles/fpm-entrypoint.sh"]
