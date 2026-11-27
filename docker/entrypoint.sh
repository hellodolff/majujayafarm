#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"

# Update Apache listening port to match platform $PORT (Railway/Render/Fly)
sed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Cache config, routes, and views at runtime using injected env vars
php artisan config:clear
if [ -n "${APP_KEY}" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec apache2-foreground
