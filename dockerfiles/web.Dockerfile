ARG UNOPIM_IMAGE=webkul/unopim
ARG UNOPIM_TAG=1.0.1

FROM ${UNOPIM_IMAGE}:${UNOPIM_TAG}

# Install HTMLPurifier cache directory and set permissions
RUN mkdir -p /var/www/html/storage/app/purifier \
    && chown -R www-data:www-data /var/www/html/storage

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

ENTRYPOINT ["/var/www/html/dockerfiles/web-entrypoint.sh"]
