#!/bin/bash

# SkillsXchangee Deployment Setup Script
# This script sets up the environment and installs dependencies for deployment

echo "🚀 Starting SkillsXchangee deployment setup..."

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found. Please run this script from the project root directory."
    exit 1
fi

# Step 1: Copy .env.backup to .env in project root directory
echo "📋 Setting up environment configuration..."
if [ -f ".env.backup" ]; then
    cp .env.backup .env
    echo "✅ Copied .env.backup to .env"
else
    echo "❌ Error: .env.backup not found in current directory"
    exit 1
fi

# Step 2: Add missing Pusher TLS settings to .env
echo "🔧 Adding Pusher TLS configuration..."
cat >> .env << 'EOF'

# Pusher TLS Settings (Set to false since Force TLS is OFF in Pusher dashboard)
PUSHER_USE_TLS=false
PUSHER_ENCRYPTED=false
VITE_PUSHER_FORCE_TLS=false
EOF
echo "✅ Added Pusher TLS settings to .env"

# Step 3: Install PHP dependencies
echo "📦 Installing PHP dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
    echo "✅ PHP dependencies installed"
else
    echo "❌ Error: Composer not found. Please install Composer first."
    exit 1
fi

# Step 4: Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
if command -v npm &> /dev/null; then
    npm install
    echo "✅ Node.js dependencies installed"
else
    echo "❌ Error: npm not found. Please install Node.js first."
    exit 1
fi

# Step 5: Build frontend assets
echo "🏗️ Building frontend assets..."
npm run build
echo "✅ Frontend assets built"

# Step 6: Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Laravel caches cleared"

# Step 7: Generate application key if not set
echo "🔑 Checking application key..."
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate
    echo "✅ Application key generated"
else
    echo "✅ Application key already set"
fi

# Step 8: Test Pusher connection
echo "🧪 Testing Pusher connection..."
if php test-pusher.php; then
    echo "✅ Pusher connection test successful"
else
    echo "⚠️ Pusher connection test failed - check your configuration"
fi

echo ""
echo "🎉 Deployment setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Update your database configuration in .env if needed"
echo "2. Run database migrations: php artisan migrate"
echo "3. Start your web server"
echo "4. Test the chat functionality"
echo ""
echo "🔧 Configuration files created:"
echo "- .env (with Pusher settings)"
echo "- Frontend assets built in public/build/"
echo ""
echo "📊 Pusher Configuration:"
echo "- App ID: 2047345"
echo "- Cluster: ap1"
echo "- TLS: Disabled (matching your Pusher dashboard)"
echo ""
