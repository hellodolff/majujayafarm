#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"

# Update Apache listening port to match platform $PORT (Railway/Render/Fly)
sed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure Laravel storage directories exist
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# Generate APP_KEY if not provided in Railway/Render environment variables
if [ -z "${APP_KEY}" ]; then
    echo "WARNING: APP_KEY is not set in environment variables. Generating a temporary key in .env..."
    php artisan key:generate --force
fi

# Rebuild caches with runtime environment variables
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure www-data owns all cache and storage files created during startup
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

exec apache2-foreground
