# =============================================================================
# UnoPim Web Server (Apache + PHP 8.3)
# =============================================================================
# Multi-stage build:
#   Stage 1 (composer) — install PHP dependencies
#   Stage 2 (node)     — build frontend assets
#   Stage 3 (app)      — final production image
# =============================================================================

# ---------------------------------------------------------------------------
# Stage 1: Composer dependencies
# ---------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ---------------------------------------------------------------------------
# Stage 2: Frontend assets
# ---------------------------------------------------------------------------
FROM node:20-alpine AS node

WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY packages/Webkul/Admin/package.json packages/Webkul/Admin/package.json
COPY packages/Webkul/Installer/package.json packages/Webkul/Installer/package.json
RUN npm ci --ignore-scripts

COPY packages/Webkul/Admin/src/Resources/ packages/Webkul/Admin/src/Resources/
COPY packages/Webkul/Installer/src/Resources/ packages/Webkul/Installer/src/Resources/
RUN npm run build 2>/dev/null || true

# ---------------------------------------------------------------------------
# Stage 3: Production image
# ---------------------------------------------------------------------------
FROM php:8.3-apache

LABEL maintainer="Webkul <support@webkul.com>"
LABEL org.opencontainers.image.title="UnoPim"
LABEL org.opencontainers.image.description="Open Source Product Information Management"
LABEL org.opencontainers.image.url="https://unopim.com"
LABEL org.opencontainers.image.source="https://github.com/unopim/unopim"
LABEL org.opencontainers.image.vendor="Webkul"
LABEL org.opencontainers.image.licenses="MIT"

# System dependencies
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
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        calendar \
        ctype \
        curl \
        dom \
        exif \
        fileinfo \
        gd \
        gmp \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo \
        pdo_mysql \
        session \
        simplexml \
        tokenizer \
        xml \
        xmlwriter \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        libxml2-dev \
        libonig-dev \
        libicu-dev \
        libgmp-dev \
    && apt-get install -y --no-install-recommends \
        libfreetype6 \
        libjpeg62-turbo \
        libpng16-16 \
        libwebp7 \
        libzip4 \
        libxml2 \
        libonig5 \
        libicu72 \
        libgmp10 \
    && rm -rf /var/lib/apt/lists/*

# PHP production configuration
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY dockerfiles/php.ini "$PHP_INI_DIR/conf.d/unopim.ini"

# Apache configuration
RUN a2enmod rewrite headers deflate \
    && sed -i 's|/var/www/html|/var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js (for runtime asset builds if needed)
COPY --from=node:20-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Application code
WORKDIR /var/www/html
COPY . .
COPY --from=composer /app/vendor ./vendor
COPY --from=node /app/public/themes ./public/themes

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=90s --retries=3 \
    CMD curl -sf http://localhost/admin/login -o /dev/null || exit 1

ENTRYPOINT ["/var/www/html/dockerfiles/web-entrypoint.sh"]
