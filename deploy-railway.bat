@echo off
REM SkillsXchange Railway Deployment Script for Windows

echo 🚀 Starting SkillsXchange Railway Deployment...

REM Check if we're in the right directory
if not exist "artisan" (
    echo ❌ Error: Not in Laravel project directory
    exit /b 1
)

REM Create .env file from railway.env if it doesn't exist
if not exist ".env" (
    echo 📝 Creating .env file from railway.env...
    copy railway.env .env
)

REM Generate application key
echo 🔑 Generating application key...
php artisan key:generate --force

REM Clear caches
echo 🧹 Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

REM Test database connection
echo 🔍 Testing database connection...
php artisan tinker --execute="try { DB::connection()->getPdo(); echo '✅ Database connected successfully'; } catch(Exception $e) { echo '❌ Database connection failed: ' . $e->getMessage(); exit(1); }"

REM Run migrations
echo 📊 Running database migrations...
php artisan migrate --force

REM Run seeders
echo 🌱 Running database seeders...
php artisan db:seed --force

REM Build assets
echo 🎨 Building assets...
npm install
npm run build

REM Cache configuration for production
echo ⚡ Caching configuration...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ✅ Railway deployment preparation complete!
echo 🚀 You can now deploy to Railway using:
echo    - Railway CLI: railway up
echo    - GitHub integration: Push to your connected repository
echo    - Manual deployment: Upload files to Railway
pause
