#!/bin/sh
set -e

echo "==> Running entrypoint.sh"

# Copy .env if not present
if [ ! -f /var/www/html/.env ]; then
    echo "==> No .env found; copying from .env.example"
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generate application key if not set
if grep -q "APP_KEY=$" /var/www/html/.env; then
    echo "==> Generating application key"
    php artisan key:generate --no-interaction
fi

# Run migrations
echo "==> Running database migrations"
php artisan migrate --force --no-interaction

# Clear and cache config
echo "==> Caching config"
php artisan config:cache --no-interaction || true

# Start services via supervisor
exec "$@"
