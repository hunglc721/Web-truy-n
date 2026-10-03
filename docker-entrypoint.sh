#!/bin/bash
set -e

cd /var/www/html/laravel-blade

# 1. Ensure .env exists
if [ ! -f .env ]; then
    cp .env.example .env
fi

# 2. Configure Apache port dynamically from Render's $PORT env
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# 3. Setup SQLite database if DB_CONNECTION is sqlite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    mkdir -p database
    touch database/database.sqlite
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
    sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/html/laravel-blade/database/database.sqlite|' .env
fi

# 4. Ensure storage directories exist
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs

# 5. Create storage symlink
php artisan storage:link || true

# 6. Ensure valid Laravel APP_KEY (must start with base64:)
if [[ ! "$APP_KEY" =~ ^base64: ]]; then
    if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
        php artisan key:generate --force
    fi
    APP_KEY=$(grep '^APP_KEY=' .env | head -n 1 | cut -d '=' -f2-)
    export APP_KEY
fi

# 7. Run database migrations and seed default data
php artisan migrate --force
php artisan db:seed --force || true

# 8. Cache configurations, routes, and views
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 9. Set final ownership and permissions for Apache (www-data)
chown -R www-data:www-data /var/www/html/laravel-blade/database /var/www/html/laravel-blade/storage /var/www/html/laravel-blade/bootstrap/cache
chmod -R 775 /var/www/html/laravel-blade/database /var/www/html/laravel-blade/storage /var/www/html/laravel-blade/bootstrap/cache

echo "==> Comicx application is ready on port ${PORT}!"
exec apache2-foreground
