# =============================================================================
# UnoPim Base Image — Shared PHP runtime for web + queue + scheduler
# =============================================================================
# This image contains PHP 8.3 with all required extensions for UnoPim.
# The web.Dockerfile and q.Dockerfile extend this to avoid duplicating
# the extension installation (~5 min build time).
# =============================================================================

FROM php:8.3-cli AS base

LABEL maintainer="Webkul <support@webkul.com>"
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
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    # Only install extensions NOT already in php:8.3
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
