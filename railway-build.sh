#!/bin/bash

echo "🚀 CompBuddy - Build Script for Railway"
echo "========================================"

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies
echo "📦 Installing Node.js dependencies..."
npm ci

# Build frontend assets
echo "🔨 Building frontend assets..."
npm run build

# Create SQLite database if not exists
echo "🗄️  Setting up database..."
touch database/database.sqlite

# Run migrations
echo "📊 Running migrations..."
php artisan migrate --force --no-interaction

# Cache configuration
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache

echo "✅ Build completed successfully!"
echo "🎉 Ready to deploy!!"
