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

# Cache Laravel configurations
echo "Caching Laravel configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "Setting permissions..."
chmod -R 755 storage bootstrap/cache

echo "✅ Build completed successfully!"
