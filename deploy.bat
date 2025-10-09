@echo off
REM SkillsXchange Deployment Script for Windows
REM Run this script on your production server

echo 🚀 Starting SkillsXchange Deployment...

REM Check if we're in the right directory
if not exist "artisan" (
    echo ❌ Error: Please run this script from the Laravel project root directory
    pause
    exit /b 1
)

REM Install/Update Composer dependencies
echo 📦 Installing Composer dependencies...
composer install --optimize-autoloader --no-dev --no-interaction

REM Install/Update NPM dependencies and build assets
echo 🎨 Building frontend assets...
npm install --production
npm run build

REM Generate application key if not exists
echo 🔑 Generating application key...
php artisan key:generate --force

REM Run database migrations
echo 🗄️ Running database migrations...
php artisan migrate --force

REM Cache configuration for production
echo ⚡ Optimizing for production...
php artisan config:cache
php artisan route:cache
php artisan view:cache

REM Clear application cache
php artisan cache:clear

echo ✅ Deployment completed successfully!
echo 🌐 Your application should now be accessible at your domain
echo 📧 Email system configured with Gmail SMTP
echo 🔒 Firebase/Google authentication removed
pause
