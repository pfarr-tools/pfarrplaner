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
    libcurl \
 && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    soap \
    dom \
    curl \
 && apk add --no-cache --virtual .build-deps g++ make autoconf \
 && pecl install yaml \
 && docker-php-ext-enable yaml \
 && apk del .build-deps

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . .

# Install Composer dependencies (no post-autoload scripts)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Install Node/Vite assets (optional)
RUN yarn install && yarn run build

# Permissions (Laravel expects writable dirs)
RUN chmod -R 775 storage bootstrap/cache || true

# Expose Octane port (defined in pfarrplaner-dockerized)
EXPOSE 9500

# No CMD/ENTRYPOINT here – pfarrplaner-dockerized takes over
