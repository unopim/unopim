# =============================================================================
# UnoPim Nginx Reverse Proxy
# =============================================================================
# Lightweight Nginx container that proxies PHP requests to the FPM container
# and serves static files (CSS, JS, images) directly.
# =============================================================================

FROM nginx:1.27-alpine@sha256:65645c7bb6a0661892a8b03b89d0743208a18dd2f3f17a54ef4b76fb8e2f2a10

LABEL maintainer="Webkul <support@webkul.com>"
LABEL org.opencontainers.image.title="UnoPim Nginx"
LABEL org.opencontainers.image.description="Nginx reverse proxy for UnoPim PIM"
LABEL org.opencontainers.image.source="https://github.com/unopim/unopim"

# Remove default config
RUN rm /etc/nginx/conf.d/default.conf

# Copy UnoPim nginx config
COPY dockerfiles/nginx.conf /etc/nginx/conf.d/unopim.conf

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -sf http://localhost/ -o /dev/null -w '%{http_code}' | grep -qE '^(200|302|502)$' || exit 1
