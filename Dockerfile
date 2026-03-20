# ──────────────────────────────────────────────
# Stage 1: Composer dependencies
# ──────────────────────────────────────────────
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ──────────────────────────────────────────────
# Stage 2: Build frontend assets (Node 22)
# ──────────────────────────────────────────────
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ resources/
COPY public/ public/

# Ziggy se importa desde vendor/, necesita estar presente en el build
COPY --from=composer /app/vendor vendor

RUN npm run build

# ──────────────────────────────────────────────
# Stage 3: PHP-FPM production image
# ──────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS app

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

RUN pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www/html

# Copiar app completa desde el stage de composer (incluye vendor optimizado)
COPY --from=composer /app .

# Copiar assets compilados desde el stage de frontend
COPY --from=frontend /app/public/build public/build

# Permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/supervisord.conf /etc/supervisord.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

CMD ["php-fpm"]
