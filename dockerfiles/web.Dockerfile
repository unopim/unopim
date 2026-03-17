FROM webkul/unopim:1.0.1

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libpq5 \
    && docker-php-ext-install pdo_pgsql pgsql \
    && apt-get purge -y libpq-dev \
    && apt-get autoremove -y --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

ENTRYPOINT ["/var/www/html/dockerfiles/web-entrypoint.sh"]

