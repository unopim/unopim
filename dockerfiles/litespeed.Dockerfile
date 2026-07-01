# =============================================================================
# UnoPim Web Server (OpenLiteSpeed + LSPHP 8.3)
# =============================================================================
# Third web-server option alongside Nginx+FPM (default) and Apache.
# Native HTTP/3 + QUIC via LiteSpeed's built-in lsquic — no extra build.
#
# Use via:
#   docker compose -f docker-compose.yml -f docker-compose.litespeed.yml up -d
#
# Multi-stage build:
#   Stage 1 (composer) — install PHP dependencies
#   Stage 2 (app)      — production OpenLiteSpeed image
# =============================================================================

# Base image tag — declared before any FROM so it is global (overridable:
# docker build --build-arg OLS_IMAGE=...)
ARG OLS_IMAGE=litespeedtech/openlitespeed:1.8.4-lsphp83

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
FROM ${OLS_IMAGE}

LABEL maintainer="Webkul <support@webkul.com>"
LABEL org.opencontainers.image.title="UnoPim LiteSpeed"
LABEL org.opencontainers.image.description="OpenLiteSpeed (HTTP/3 + QUIC) application server for UnoPim PIM"
LABEL org.opencontainers.image.source="https://github.com/unopim/unopim"
LABEL org.opencontainers.image.vendor="Webkul"
LABEL org.opencontainers.image.licenses="MIT"

# LSPHP extensions to match the Nginx/FPM + Apache images.
# lsphp83-common already bundles bcmath, calendar, exif, gd, gmp, intl, zip;
# add the database, cache and TLS-cert tooling on top.
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        openssl \
        lsphp83-mysql \
        lsphp83-redis \
        lsphp83-opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Expose the LSPHP CLI as `php` so the entrypoint's artisan/composer calls work
RUN ln -sf /usr/local/lsws/lsphp83/bin/php /usr/local/bin/php

# Install Composer (for runtime use in entrypoint)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP configuration — loaded via a dedicated scan dir (see extprocessor env)
RUN mkdir -p /usr/local/lsws/lsphp83/etc/php/8.3/litespeed/conf.d
COPY dockerfiles/php.ini /usr/local/lsws/lsphp83/etc/php/8.3/litespeed/conf.d/unopim.ini

# OpenLiteSpeed server + virtual-host configuration
COPY dockerfiles/litespeed/httpd_config.conf /usr/local/lsws/conf/httpd_config.conf
COPY dockerfiles/litespeed/vhconf.conf       /usr/local/lsws/conf/vhosts/unopim/vhconf.conf

# TLS cert directory only. The dev self-signed cert is generated at RUNTIME by
# the entrypoint, so a private key is never baked into an image layer (and thus
# never shipped in the registry). Production: mount real certs over this dir.
# Do NOT pre-create /tmp/lshttpd — LiteSpeed creates it (and the LSPHP/cgid
# socket) at start-up with the right ownership for the `nobody` worker.
RUN mkdir -p /usr/local/lsws/conf/cert \
    && chown -R nobody:nogroup /usr/local/lsws/conf

# Application code + Composer vendor from stage 1
WORKDIR /var/www/html
COPY . .
COPY --from=composer /app/vendor ./vendor

# Set permissions — owned by the LSPHP worker user (nobody)
RUN chown -R nobody:nogroup storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80 443 443/udp

HEALTHCHECK --interval=30s --timeout=10s --start-period=90s --retries=3 \
    CMD curl -sf http://localhost/ -o /dev/null -w '%{http_code}' | grep -qE '^(200|302)$' || exit 1

ENTRYPOINT ["/var/www/html/dockerfiles/litespeed-entrypoint.sh"]
