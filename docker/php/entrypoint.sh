#!/bin/sh
set -e

echo "📁 Preparando directorios..."
mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "⏳ Esperando MySQL..."
until php artisan db:monitor --max=1 2>/dev/null; do
  sleep 2
done

echo "🔍 Descubriendo paquetes..."
php artisan package:discover --ansi

echo "🔄 Ejecutando migraciones..."
php artisan migrate --force --no-interaction

echo "⚡ Optimizando..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force 2>/dev/null || true

echo "🌐 Iniciando Nginx + PHP-FPM..."
service nginx start
exec php-fpm
