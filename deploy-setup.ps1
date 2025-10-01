# SkillsXchangee Deployment Setup Script for PowerShell
# This script sets up the environment and installs dependencies for deployment

Write-Host "🚀 Starting SkillsXchangee deployment setup..." -ForegroundColor Green

# Check if we're in the right directory
if (-not (Test-Path "composer.json")) {
    Write-Host "❌ Error: composer.json not found. Please run this script from the project root directory." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Step 1: Copy .env.backup to .env in project root directory
Write-Host "📋 Setting up environment configuration..." -ForegroundColor Yellow
if (Test-Path ".env.backup") {
    Copy-Item ".env.backup" ".env"
    Write-Host "✅ Copied .env.backup to .env" -ForegroundColor Green
} else {
    Write-Host "❌ Error: .env.backup not found in current directory" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Step 2: Add missing Pusher TLS settings to .env
Write-Host "🔧 Adding Pusher TLS configuration..." -ForegroundColor Yellow
$pusherConfig = @"

# Pusher TLS Settings (Set to false since Force TLS is OFF in Pusher dashboard)
PUSHER_USE_TLS=false
PUSHER_ENCRYPTED=false
VITE_PUSHER_FORCE_TLS=false
"@
Add-Content -Path ".env" -Value $pusherConfig
Write-Host "✅ Added Pusher TLS settings to .env" -ForegroundColor Green

# Step 3: Install PHP dependencies
Write-Host "📦 Installing PHP dependencies..." -ForegroundColor Yellow
try {
    & composer install --no-dev --optimize-autoloader
    Write-Host "✅ PHP dependencies installed" -ForegroundColor Green
} catch {
    Write-Host "❌ Error: Composer not found. Please install Composer first." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Step 4: Install Node.js dependencies
Write-Host "📦 Installing Node.js dependencies..." -ForegroundColor Yellow
try {
    & npm install
    Write-Host "✅ Node.js dependencies installed" -ForegroundColor Green
} catch {
    Write-Host "❌ Error: npm not found. Please install Node.js first." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Step 5: Build frontend assets
Write-Host "🏗️ Building frontend assets..." -ForegroundColor Yellow
& npm run build
Write-Host "✅ Frontend assets built" -ForegroundColor Green

# Step 6: Clear Laravel caches
Write-Host "🧹 Clearing Laravel caches..." -ForegroundColor Yellow
& php artisan config:clear
& php artisan cache:clear
& php artisan route:clear
& php artisan view:clear
Write-Host "✅ Laravel caches cleared" -ForegroundColor Green

# Step 7: Generate application key if not set
Write-Host "🔑 Checking application key..." -ForegroundColor Yellow
$envContent = Get-Content ".env" -Raw
if ($envContent -notmatch "APP_KEY=base64:") {
    & php artisan key:generate
    Write-Host "✅ Application key generated" -ForegroundColor Green
} else {
    Write-Host "✅ Application key already set" -ForegroundColor Green
}

# Step 8: Test Pusher connection
Write-Host "🧪 Testing Pusher connection..." -ForegroundColor Yellow
try {
    & php test-pusher.php
    Write-Host "✅ Pusher connection test successful" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Pusher connection test failed - check your configuration" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🎉 Deployment setup completed!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Next steps:" -ForegroundColor Cyan
Write-Host "1. Update your database configuration in .env if needed"
Write-Host "2. Run database migrations: php artisan migrate"
Write-Host "3. Start your web server"
Write-Host "4. Test the chat functionality"
Write-Host ""
Write-Host "🔧 Configuration files created:" -ForegroundColor Cyan
Write-Host "- .env (with Pusher settings)"
Write-Host "- Frontend assets built in public\build\"
Write-Host ""
Write-Host "📊 Pusher Configuration:" -ForegroundColor Cyan
Write-Host "- App ID: 2047345"
Write-Host "- Cluster: ap1"
Write-Host "- TLS: Disabled (matching your Pusher dashboard)"
Write-Host ""
Read-Host "Press Enter to exit"
