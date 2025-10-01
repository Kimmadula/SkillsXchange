#!/bin/bash

# SkillsXchange Asset Build Script
echo "Building SkillsXchange assets..."

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Build assets
echo "Building CSS and JS assets..."
npm run build

# Clear Laravel caches
echo "Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Optimize for production
echo "Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Assets built successfully!"
echo "CSS and JS files are now available in public/build/"
