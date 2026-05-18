FROM debian:bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
      ca-certificates curl gnupg lsb-release git \
    && curl -sSLo /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
    && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list \
    && apt-get update && apt-get install -y --no-install-recommends \
      php8.4-cli php8.4-swoole php8.4-mbstring \
      php8.4-gd php8.4-intl php8.4-zip php8.4-xml php8.4-curl php8.4-mysql php8.4-yaml \
      locales tzdata \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && sed -i '/^path-exclude=\/usr\/share\/locale/d' /etc/dpkg/dpkg.cfg.d/docker 2>/dev/null || true \
    && echo "de_DE.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen \
    && update-locale LANG=de_DE.UTF-8 \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV LANG=de_DE.UTF-8 \
    LANGUAGE=de_DE:de \
    LC_ALL=de_DE.UTF-8 \
    TZ=Europe/Berlin

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-scripts \
 && npm install && npm run build \
 && mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && rm -f app/Console/Commands/DuskRunCommand.php

EXPOSE 9500

# No CMD/ENTRYPOINT – handled by pfarrplaner-dockerized
