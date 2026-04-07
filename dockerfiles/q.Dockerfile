ARG UNOPIM_IMAGE=webkul/unopim
ARG UNOPIM_TAG=1.0.1

FROM ${UNOPIM_IMAGE}:${UNOPIM_TAG}

# Queue worker does not need Apache
ENTRYPOINT ["/var/www/html/dockerfiles/q-entrypoint.sh"]
