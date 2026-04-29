#!/usr/bin/env bash
set -e

PORT="${PORT:-10000}"
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:${PORT}}}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

php artisan config:clear
php artisan view:clear
php artisan migrate --force

if [ "${RUN_SEEDER:-false}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan view:cache

exec apache2-foreground
