ARG UNOPIM_IMAGE=webkul/unopim
ARG UNOPIM_TAG=1.0.1

FROM ${UNOPIM_IMAGE}:${UNOPIM_TAG}

HEALTHCHECK --interval=30s --timeout=10s --start-period=90s --retries=3 \
    CMD curl -sf http://localhost/admin/login -o /dev/null || exit 1

ENTRYPOINT ["/var/www/html/dockerfiles/web-entrypoint.sh"]
