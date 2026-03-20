#!/bin/sh
set -e

echo "⏳ Esperando MySQL..."
until php artisan db:monitor --max=1 2>/dev/null; do
  sleep 2
done

echo "🔄 Ejecutando migraciones..."
php artisan migrate --force --no-interaction

echo "⚡ Optimizando..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force 2>/dev/null || true

echo "✅ Iniciando PHP-FPM..."
exec php-fpm
