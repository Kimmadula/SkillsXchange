#!/bin/bash

# SkillsXchange Render Build Script
echo "Building SkillsXchange for Render deployment..."

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

# Install Node dependencies
echo "Installing Node dependencies..."
npm install

# Build assets
echo "Building CSS and JS assets..."
npm run build

# Check if build was successful
if [ $? -eq 0 ]; then
    echo "✅ Assets built successfully!"
else
    echo "⚠️ Asset build failed, using fallback CSS"
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear and cache Laravel configurations
echo "Clearing and caching Laravel configurations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "Caching Laravel configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "Setting permissions..."
chmod -R 755 storage bootstrap/cache

echo "✅ Build completed successfully!"
