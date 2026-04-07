# =============================================================================
# UnoPim Queue Worker
# =============================================================================
# Lightweight image for processing background jobs.
# Shares the same base as the web image but runs queue:work instead of Apache.
# =============================================================================

FROM php:8.3-cli

LABEL maintainer="Webkul <support@webkul.com>"
LABEL org.opencontainers.image.title="UnoPim Queue Worker"
LABEL org.opencontainers.image.description="Background job processor for UnoPim PIM"

# System dependencies (same as web, minus Apache-specific)
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
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

WORKDIR /var/www/html

ENTRYPOINT ["/var/www/html/dockerfiles/q-entrypoint.sh"]
