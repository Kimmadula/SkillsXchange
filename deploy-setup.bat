@echo off
REM SkillsXchangee Deployment Setup Script for Windows
REM This script sets up the environment and installs dependencies for deployment

echo 🚀 Starting SkillsXchangee deployment setup...

REM Check if we're in the right directory
if not exist "composer.json" (
    echo ❌ Error: composer.json not found. Please run this script from the project root directory.
    pause
    exit /b 1
)

REM Step 1: Copy .env.backup to .env in project root directory
echo 📋 Setting up environment configuration...
if exist ".env.backup" (
    copy ".env.backup" ".env" >nul
    echo ✅ Copied .env.backup to .env
) else (
    echo ❌ Error: .env.backup not found in current directory
    pause
    exit /b 1
)

REM Step 2: Add missing Pusher TLS settings to .env
echo 🔧 Adding Pusher TLS configuration...
echo. >> .env
echo # Pusher TLS Settings (Set to false since Force TLS is OFF in Pusher dashboard) >> .env
echo PUSHER_USE_TLS=false >> .env
echo PUSHER_ENCRYPTED=false >> .env
echo VITE_PUSHER_FORCE_TLS=false >> .env
echo ✅ Added Pusher TLS settings to .env

REM Step 3: Install PHP dependencies
echo 📦 Installing PHP dependencies...
where composer >nul 2>nul
if %errorlevel% equ 0 (
    composer install --no-dev --optimize-autoloader
    echo ✅ PHP dependencies installed
) else (
    echo ❌ Error: Composer not found. Please install Composer first.
    pause
    exit /b 1
)

REM Step 4: Install Node.js dependencies
echo 📦 Installing Node.js dependencies...
where npm >nul 2>nul
if %errorlevel% equ 0 (
    npm install
    echo ✅ Node.js dependencies installed
) else (
    echo ❌ Error: npm not found. Please install Node.js first.
    pause
    exit /b 1
)

REM Step 5: Build frontend assets
echo 🏗️ Building frontend assets...
npm run build
echo ✅ Frontend assets built

REM Step 6: Clear Laravel caches
echo 🧹 Clearing Laravel caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo ✅ Laravel caches cleared

REM Step 7: Generate application key if not set
echo 🔑 Checking application key...
findstr /C:"APP_KEY=base64:" .env >nul
if %errorlevel% neq 0 (
    php artisan key:generate
    echo ✅ Application key generated
) else (
    echo ✅ Application key already set
)

REM Step 8: Test Pusher connection
echo 🧪 Testing Pusher connection...
php test-pusher.php
if %errorlevel% equ 0 (
    echo ✅ Pusher connection test successful
) else (
    echo ⚠️ Pusher connection test failed - check your configuration
)

echo.
echo 🎉 Deployment setup completed!
echo.
echo 📋 Next steps:
echo 1. Update your database configuration in .env if needed
echo 2. Run database migrations: php artisan migrate
echo 3. Start your web server
echo 4. Test the chat functionality
echo.
echo 🔧 Configuration files created:
echo - .env (with Pusher settings)
echo - Frontend assets built in public\build\
echo.
echo 📊 Pusher Configuration:
echo - App ID: 2047345
echo - Cluster: ap1
echo - TLS: Disabled (matching your Pusher dashboard)
echo.
pause
