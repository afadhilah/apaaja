#!/bin/bash

# Create storage directories if they don't exist
mkdir -p storage/framework/{sessions,views,cache,testing}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
chmod -R 775 storage bootstrap/cache

# Clear and cache config (production optimization)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start FrankenPHP
frankenphp run --config /Caddyfile
