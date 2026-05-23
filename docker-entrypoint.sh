#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

# Cache configuration, routes and views for production
echo "Caching Laravel configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

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
