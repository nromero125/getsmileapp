# ──────────────────────────────────────────────
# Stage 1: PHP con extensiones + composer install
# ──────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS php-base

RUN apk add --no-cache \
    bash curl git unzip \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    oniguruma-dev libxml2-dev icu-dev zip libzip-dev supervisor \
    autoconf g++ make linux-headers

RUN docker-php-ext-configure gd \
        --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql gd bcmath mbstring xml zip opcache pcntl intl

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN COMPOSER_MEMORY_LIMIT=-1 composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --ignore-platform-reqs

COPY . .
RUN mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/logs \
    && chmod -R 775 bootstrap/cache storage \
    && COMPOSER_MEMORY_LIMIT=-1 composer dump-autoload --optimize --no-dev --ignore-platform-reqs

# ──────────────────────────────────────────────
# Stage 2: Build frontend (Node 22)
# ──────────────────────────────────────────────
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ resources/
COPY public/ public/

# Ziggy necesita vendor/ en tiempo de build
COPY --from=php-base /var/www/html/vendor vendor

RUN npm run build

# ──────────────────────────────────────────────
# Stage 3: Imagen final de producción
# ──────────────────────────────────────────────
FROM php-base AS app

# Reemplazar public/build con los assets compilados
COPY --from=frontend /app/public/build public/build

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/supervisord.conf /etc/supervisord.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

CMD ["php-fpm"]
