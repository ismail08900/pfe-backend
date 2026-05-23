#!/bin/sh

# Cache configuration, routes and views for production
echo "Caching Laravel configuration..."
php artisan config:cache || echo "Config cache failed, continuing..."
php artisan route:cache || echo "Route cache failed, continuing..."
php artisan view:cache || echo "View cache failed, continuing..."

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm -D

# Run migrations
echo "Running database migrations..."
php artisan migrate --force || echo "Migrations failed, continuing..."

# Seed database
echo "Seeding database..."
php artisan db:seed --force || echo "Seeding failed, continuing..."

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g "daemon off;"
