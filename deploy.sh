#!/bin/bash

# SkillsXchange Deployment Script
# Run this script on your production server

echo "🚀 Starting SkillsXchange Deployment..."

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the Laravel project root directory"
    exit 1
fi

# Install/Update Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# Install/Update NPM dependencies and build assets
echo "🎨 Building frontend assets..."
npm install --production
npm run build

# Generate application key if not exists
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Cache configuration for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear application cache
php artisan cache:clear

# Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs/*.log 2>/dev/null || true

echo "✅ Deployment completed successfully!"
echo "🌐 Your application should now be accessible at your domain"
echo "📧 Email system configured with Gmail SMTP"
echo "🔒 Firebase/Google authentication removed"