FROM debian:bookworm-slim AS base
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates curl gnupg lsb-release git unzip mariadb-client \
 && curl -sSLo /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
 && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list \
 && apt-get update && apt-get install -y --no-install-recommends php8.4-cli php8.4-fpm php8.4-mbstring php8.4-gd php8.4-intl php8.4-zip php8.4-xml php8.4-curl php8.4-mysql php8.4-yaml php8.4-posix locales tzdata \
 && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
 && apt-get install -y --no-install-recommends nodejs \
 && sed -i 's|^listen = .*|listen = 9000|' /etc/php/8.4/fpm/pool.d/www.conf \
 && sed -i -E 's|^[[:space:];]*clear_env[[:space:]]*=.*$|clear_env = no|' /etc/php/8.4/fpm/pool.d/www.conf \
 && sed -i -E 's|^[[:space:];]*catch_workers_output[[:space:]]*=.*$|catch_workers_output = yes|' /etc/php/8.4/fpm/pool.d/www.conf \
 && echo 'de_DE.UTF-8 UTF-8' > /etc/locale.gen && locale-gen \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && rm -rf /var/lib/apt/lists/*
ENV LANG=de_DE.UTF-8 LANGUAGE=de_DE:de LC_ALL=de_DE.UTF-8 TZ=Europe/Berlin
WORKDIR /var/www/html
COPY src/ /var/www/html/
RUN composer install --no-interaction --prefer-dist --no-scripts \
 && cp -a vendor /opt/pfarrplaner-vendor \
 && npm install \
 && mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache
RUN printf '%s\n' \
  '#!/bin/sh' \
  'set -eu' \
  'if [ ! -f /var/www/html/vendor/autoload.php ]; then' \
  '  mkdir -p /var/www/html/vendor' \
  '  cp -a /opt/pfarrplaner-vendor/. /var/www/html/vendor/' \
  'fi' \
  'mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/bootstrap/cache /var/backups/pfarrplaner' \
  'chmod -R a+rwX /var/www/html/storage /var/www/html/bootstrap/cache /var/backups/pfarrplaner' \
  '(cd /var/www/html && php artisan config:clear >/dev/null 2>&1 || true)' \
  'exec "$@"' \
  > /usr/local/bin/pfarrplaner-dev-entrypoint \
 && chmod +x /usr/local/bin/pfarrplaner-dev-entrypoint

FROM base AS development
RUN printf '#!/bin/sh\nexec php-fpm8.4 -t\n' > /usr/local/bin/php-fpm-healthcheck && chmod +x /usr/local/bin/php-fpm-healthcheck
CMD ["php-fpm8.4", "-F"]

FROM base AS production
RUN apt-get update && apt-get install -y --no-install-recommends php8.4-swoole \
 && rm -rf /var/lib/apt/lists/*
RUN npm run build && composer dump-autoload --optimize
RUN printf '#!/bin/sh\nexec php-fpm8.4 -t\n' > /usr/local/bin/php-fpm-healthcheck && chmod +x /usr/local/bin/php-fpm-healthcheck
CMD ["php-fpm8.4", "-F"]

FROM node:22-bookworm-slim AS node
WORKDIR /app
