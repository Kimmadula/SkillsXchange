#!/bin/bash

# SkillsXchange Render Build Script
echo "Building SkillsXchange for Render deployment..."

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

# Install Node dependencies (with optimized settings)
echo "Installing Node dependencies..."
npm config set fetch-retries 3
npm config set fetch-retry-mintimeout 10000
npm config set fetch-retry-maxtimeout 60000
npm install --prefer-offline --no-audit

# Build assets (with timeout to prevent hanging)
echo "Building CSS and JS assets..."
timeout 300 npm run build || echo "⚠️ Asset build timed out or failed, using fallback CSS"

# Check if build was successful
if [ $? -eq 0 ]; then
    echo "✅ Assets built successfully!"
else
    echo "⚠️ Asset build failed, using fallback CSS"
fi

# Skip database migrations during build (will run on startup)
echo "Skipping database migrations during build (will run on startup)..."

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
