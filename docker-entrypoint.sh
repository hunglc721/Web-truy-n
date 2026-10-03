#!/bin/bash
set -e

cd /var/www/html/laravel-blade

# Configure Apache port dynamically from Render's $PORT env
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Setup SQLite database if DB_CONNECTION is sqlite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    mkdir -p database
    touch database/database.sqlite
    chown -R www-data:www-data database
    chmod -R 775 database
fi

# Ensure storage directories exist with correct permissions
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create storage symlink
php artisan storage:link || true

# Generate application key if missing
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run database migrations
php artisan migrate --force || true

# Cache configurations and routes for production performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "==> Comicx application is ready on port ${PORT}!"
exec apache2-foreground
