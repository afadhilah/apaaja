#!/bin/bash

# Install PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies
npm install

# Build frontend assets
npm run build

# Generate app key if not exists
php artisan key:generate --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Build completed successfully!"
