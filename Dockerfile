FROM phpswoole/swoole:php8.4-alpine

# ---- RUNTIME deps (stay in final image)
RUN set -eux; \
  for i in 1 2 3; do \
    apk add --no-cache \
      bash curl git \
      libzip libxml2 libpng freetype libjpeg-turbo libwebp zlib \
      nodejs npm yarn \
      icu-libs tzdata gettext musl-locales gcompat \
      liburing libyaml libstdc++ \
    && break || (echo "apk retry $i" && sleep 2); \
  done

# ---- BUILD deps (removed later) + PHP extensions
RUN set -eux; \
  apk add --no-cache --virtual .build-deps \
      build-base autoconf pkgconfig \
      libzip-dev libxml2-dev libpng-dev freetype-dev libjpeg-turbo-dev libwebp-dev zlib-dev \
      oniguruma-dev icu-dev curl-dev yaml-dev; \
  docker-php-source extract; \
  export CPPFLAGS="${CPPFLAGS:-} -I/usr/src/php"; \
  docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
  docker-php-ext-install pdo pdo_mysql zip soap dom curl intl gd; \
  pecl install yaml; \
  docker-php-ext-enable yaml; \
  docker-php-source delete; \
  apk del .build-deps


# Set locale to German (de_DE.UTF-8)
ENV LANG=de_DE.UTF-8 \
    LANGUAGE=de_DE:de \
    LC_ALL=de_DE.UTF-8

# Set timezone to Europe/Berlin
ENV TZ=Europe/Berlin

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . .

# Install Composer dependencies (no post-autoload scripts)
RUN composer install --no-dev --optimize-autoloader --no-scripts \
 && yarn install && yarn run prod \
 && chmod -R 775 storage bootstrap/cache || true

# Expose Octane port
EXPOSE 9500

# No CMD/ENTRYPOINT – handled by pfarrplaner-dockerized
