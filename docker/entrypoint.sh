#!/bin/sh
set -eu

cd /var/www/html

mkdir -p storage/app/private storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ "${DB_CONNECTION:-}" = "mysql" ] && [ -n "${DB_HOST:-}" ]; then
    until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" --silent; do
        echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
        sleep 2
    done
fi

if [ "${RUN_STORAGE_LINK:-true}" = "true" ]; then
    php artisan storage:link || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_LARAVEL_CACHE:-true}" = "true" ]; then
    php artisan optimize:clear
    php artisan config:cache
    php artisan view:cache
    php artisan event:cache
fi

exec "$@"
