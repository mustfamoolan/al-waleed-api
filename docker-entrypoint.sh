#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysqladmin ping -h mysql -u root -p${DB_ROOT_PASSWORD:-root_password} --silent; do
    sleep 1
done

echo "MySQL is ready!"

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install dependencies if vendor doesn't exist
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

# Generate application key if not exists
if [ ! -f "/var/www/html/.env" ]; then
    echo "Creating .env file..."
    if [ -f "/var/www/html/env.docker.example" ]; then
        cp /var/www/html/env.docker.example /var/www/html/.env
    elif [ -f "/var/www/html/.env.example" ]; then
        cp /var/www/html/.env.example /var/www/html/.env
    fi
    php artisan key:generate --force
fi

# Clear and cache config
echo "Optimizing Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Optimize for production
if [ "${APP_ENV}" = "production" ]; then
    echo "Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Start Apache
echo "Starting Apache..."
exec "$@"

