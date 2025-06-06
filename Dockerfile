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
    icu-dev \
    libcurl \
    nodejs \
    npm \
    yarn \
    g++ \
    make \
    autoconf

# Install core PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    soap \
    dom \
    curl \
    intl

# Install GD (with freetype, jpeg, webp)
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
 && docker-php-ext-install gd

# Install YAML extension
RUN pecl install yaml \
 && docker-php-ext-enable yaml

# Clean up
RUN apk del g++ make autoconf

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . .

# Install Composer dependencies (no post-autoload scripts)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Install Node/Vite assets
RUN yarn install && yarn run build

# Permissions (Laravel expects writable dirs)
RUN chmod -R 775 storage bootstrap/cache || true

# Expose Octane port (defined in pfarrplaner-dockerized)
EXPOSE 9500

# Hinweis: kein CMD/ENTRYPOINT – wird extern über pfarrplaner-dockerized gesteuert
