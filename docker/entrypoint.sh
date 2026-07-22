#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

export PORT="${PORT:-80}"

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
envsubst '${PORT}' < /etc/apache2/sites-available/000-default.conf > /tmp/eims-vhost.conf
mv /tmp/eims-vhost.conf /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is missing. Set APP_KEY in Render using: php artisan key:generate --show"
    exit 1
fi

php artisan config:clear --no-interaction || true
php artisan route:clear --no-interaction || true
php artisan view:clear --no-interaction || true

php artisan migrate --force --no-interaction

if [ "${EIMS_RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

php artisan storage:link --force --no-interaction || true
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

exec "$@"
