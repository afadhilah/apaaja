#!/bin/bash
set -e

echo "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

echo "Installing Node dependencies..."
npm ci --production=false

echo "Building frontend assets..."
npm run build

echo "Creating storage directories..."
mkdir -p storage/framework/{sessions,views,cache,testing}
mkdir -p storage/logs
mkdir -p bootstrap/cache

echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Build completed successfully!"
