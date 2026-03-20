# ──────────────────────────────────────────────
# Stage 1: Build frontend assets (Node 22)
# ──────────────────────────────────────────────
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci --prefer-offline

COPY resources/ resources/
COPY vite.config.js ./
COPY tailwind.config.js ./
COPY postcss.config.js ./
COPY public/ public/

RUN npm run build

# ──────────────────────────────────────────────
# Stage 2: PHP-FPM production image
# ──────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS app

# System dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    zip \
    libzip-dev \
    supervisor

# PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        bcmath \
        mbstring \
        xml \
        zip \
        opcache \
        pcntl \
        intl

# Redis extension via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (layer cache)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Copy full app
COPY . .

# Copy built assets from stage 1
COPY --from=frontend /app/public/build public/build

# Finish composer setup
RUN composer dump-autoload --optimize --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/supervisord.conf /etc/supervisord.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

# Default: php-fpm (workers y scheduler sobreescriben el CMD)
CMD ["php-fpm"]
