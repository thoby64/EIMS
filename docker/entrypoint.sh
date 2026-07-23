#!/usr/bin/env bash
set -e

cd /var/www/html

export PORT="${PORT:-10000}"

# Configure Apache to listen on Render's port
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

envsubst '${PORT}' \
    < /etc/apache2/sites-available/000-default.conf \
    > /tmp/000-default.conf

mv /tmp/000-default.conf \
    /etc/apache2/sites-available/000-default.conf

# Laravel directories
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# APP_KEY must exist
if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set."
    echo ""
    echo "Generate one locally:"
    echo ""
    echo "php artisan key:generate --show"
    echo ""
    exit 1
fi

# Clear caches
php artisan optimize:clear --no-interaction || true

# Run migrations (optional)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

# Seed database (optional)
if [ "${EIMS_RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

# Storage link
php artisan storage:link || true

# Cache Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"

