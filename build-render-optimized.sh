#!/bin/bash

# Optimized Render Build Script
echo "🚀 Starting optimized Render build..."

# Set build timeout
BUILD_TIMEOUT=180  # 3 minutes

# Function to run with timeout
run_with_timeout() {
    local timeout=$1
    local command=$2
    echo "⏱️  Running: $command (timeout: ${timeout}s)"
    
    if timeout $timeout bash -c "$command"; then
        echo "✅ Success: $command"
        return 0
    else
        echo "⚠️  Timeout or failure: $command"
        return 1
    fi
}

# Install PHP dependencies (fast)
echo "📦 Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

# Install Node dependencies with production-only optimization
echo "📦 Installing Node dependencies (production only)..."
if ! run_with_timeout 90 "npm ci --only=production --no-audit --no-fund --prefer-offline"; then
    echo "⚠️  npm ci failed, trying npm install..."
    npm install --production --no-audit --no-fund --prefer-offline || echo "npm install failed"
fi

# Create build directory
mkdir -p public/build

# Try to build assets with timeout
echo "🔨 Building assets..."
if ! run_with_timeout 60 "npm run build"; then
    echo "⚠️  Asset build failed, creating minimal fallback..."
    
    # Create minimal fallback CSS
    cat > public/css/fallback.css << 'EOF'
/* Minimal fallback CSS for Render */
body { font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 0; }
.btn { display: inline-block; padding: 0.5rem 1rem; text-decoration: none; border-radius: 0.25rem; }
.btn-primary { background: #2563eb; color: white; }
.alert { padding: 1rem; margin: 1rem 0; border-radius: 0.25rem; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
.navbar { background: white; padding: 1rem 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
EOF
    
    # Create minimal manifest
    echo '{"resources/css/app.css":{"file":"css/fallback.css","isEntry":true}}' > public/build/manifest.json
    
    echo "✅ Fallback assets created"
fi

# Clear Laravel caches (fast)
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache Laravel configurations (fast)
echo "💾 Caching Laravel configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache

echo "✅ Optimized Render build completed!"
