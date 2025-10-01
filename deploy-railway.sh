#!/bin/bash

# SkillsXchange Railway Deployment Script
echo "🚀 Starting SkillsXchange Railway Deployment..."

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel project directory"
    exit 1
fi

# Create .env file from railway.env if it doesn't exist
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file from railway.env..."
    cp railway.env .env
fi

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Test database connection
echo "🔍 Testing database connection..."
php artisan tinker --execute="try { DB::connection()->getPdo(); echo '✅ Database connected successfully'; } catch(Exception \$e) { echo '❌ Database connection failed: ' . \$e->getMessage(); exit(1); }"

# Run migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Run seeders
echo "🌱 Running database seeders..."
php artisan db:seed --force

# Build assets
echo "🎨 Building assets..."
npm install
npm run build

# Cache configuration for production
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Railway deployment preparation complete!"
echo "🚀 You can now deploy to Railway using:"
echo "   - Railway CLI: railway up"
echo "   - GitHub integration: Push to your connected repository"
echo "   - Manual deployment: Upload files to Railway"
