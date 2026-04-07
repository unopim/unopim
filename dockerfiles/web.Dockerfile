# =============================================================================
# UnoPim Web Server (Apache + PHP 8.3)
# =============================================================================
# Multi-stage build:
#   Stage 1 (composer) — install PHP dependencies
#   Stage 2 (app)      — final production image
#
# Pre-built frontend assets (public/themes/) are committed in the repo,
# so no Node.js build step is needed. For development, run npm run dev
# on the host or inside the container.
# =============================================================================

# ---------------------------------------------------------------------------
# Stage 1: Composer dependencies
# ---------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app
COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Stage 2: Production image
# ---------------------------------------------------------------------------
FROM php:8.3-apache

LABEL maintainer="Webkul <support@webkul.com>"
LABEL org.opencontainers.image.title="UnoPim"
LABEL org.opencontainers.image.description="Open Source Product Information Management"
LABEL org.opencontainers.image.url="https://unopim.com"
LABEL org.opencontainers.image.source="https://github.com/unopim/unopim"
LABEL org.opencontainers.image.vendor="Webkul"
LABEL org.opencontainers.image.licenses="MIT"

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
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    # Only install extensions NOT already in php:8.3-apache
    # Already included: ctype, curl, dom, fileinfo, iconv, mbstring,
    #   openssl, pdo, session, simplexml, tokenizer, xml, xmlwriter, opcache
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
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP production configuration
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY dockerfiles/php.ini "$PHP_INI_DIR/conf.d/unopim.ini"

# Apache: enable modules and set document root to public/
RUN a2enmod rewrite headers deflate \
    && sed -i 's|/var/www/html|/var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Composer (for runtime use: composer install in entrypoint)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js runtime (for npm run dev during development)
COPY --from=node:20-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Application code + Composer vendor from stage 1
WORKDIR /var/www/html
COPY . .
COPY --from=composer /app/vendor ./vendor

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=90s --retries=3 \
    CMD curl -sf http://localhost/ -o /dev/null -w '%{http_code}' | grep -qE '^(200|302)$' || exit 1

ENTRYPOINT ["/var/www/html/dockerfiles/web-entrypoint.sh"]
