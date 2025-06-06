FROM phpswoole/swoole:php8.4-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    bash \
    libzip-dev \
    libxml2-dev \
    libpng-dev \
    oniguruma-dev \
    yaml-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    zlib-dev \
    nodejs \
    npm \
    yarn \
    icu-dev \
    curl-dev \
    pkgconfig \
    gcompat \
    tzdata \
    gettext \
    musl-locales \
 && docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
 && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    soap \
    dom \
    curl \
    intl \
    gd \
 && apk add --no-cache --virtual .build-deps g++ make autoconf \
 && pecl install yaml \
 && docker-php-ext-enable yaml \
 && apk del .build-deps

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
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Install Node/Vite assets
RUN yarn install && yarn run build

# Set permissions
RUN chmod -R 775 storage bootstrap/cache || true

# Expose Octane port
EXPOSE 9500

# No CMD/ENTRYPOINT – handled by pfarrplaner-dockerized
