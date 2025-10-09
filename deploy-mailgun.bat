@echo off
REM SkillsXchange Deployment Script with Mailgun Configuration
REM This script prepares your application for deployment with Mailgun email delivery

echo 🚀 SkillsXchange Deployment with Mailgun Setup
echo ==============================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo ❌ Error: Please run this script from the Laravel project root directory
    pause
    exit /b 1
)

echo 📧 Setting up Mailgun for email delivery...
echo.

REM Install Mailgun package if not already installed
echo 📦 Installing Mailgun package...
composer require mailgun/mailgun-php --no-interaction

REM Clear and rebuild caches
echo 🧹 Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

REM Build frontend assets
echo 🎨 Building frontend assets...
npm install
npm run build

REM Set production environment
echo ⚙️ Setting production environment...
set APP_ENV=production
set APP_DEBUG=false

REM Display configuration instructions
echo.
echo 📋 MAILGUN CONFIGURATION REQUIRED:
echo ==================================
echo.
echo 1. Sign up for Mailgun: https://www.mailgun.com/
echo 2. Get your credentials from: https://app.mailgun.com/
echo 3. Update these environment variables in your deployment platform:
echo.
echo    MAIL_MAILER=mailgun
echo    MAIL_HOST=api.mailgun.net
echo    MAIL_PORT=443
echo    MAIL_USERNAME=null
echo    MAIL_PASSWORD=null
echo    MAIL_ENCRYPTION=tls
echo    MAIL_FROM_ADDRESS=asdtumakay@gmail.com
echo    MAIL_FROM_NAME=SkillsXchange
echo    MAILGUN_DOMAIN=your-domain.mailgun.org
echo    MAILGUN_SECRET=key-your-secret-key-here
echo    MAILGUN_ENDPOINT=api.mailgun.net
echo.

REM Test email configuration if Mailgun is configured
if not "%MAILGUN_DOMAIN%"=="" if not "%MAILGUN_SECRET%"=="" (
    echo 🧪 Testing email configuration...
    php artisan test:email test@example.com
) else (
    echo ⚠️  Mailgun not configured yet. Please set MAILGUN_DOMAIN and MAILGUN_SECRET
)

echo.
echo ✅ Deployment preparation complete!
echo.
echo 🎯 Next steps:
echo    1. Configure Mailgun credentials in your deployment platform
echo    2. Deploy to your hosting platform (Render, Heroku, etc.)
echo    3. Test email delivery after deployment
echo.
echo 📚 Documentation:
echo    - Mailgun Setup Guide: MAILGUN_SETUP_GUIDE.md
echo    - Deployment Guide: deployment-guide.md
echo.
echo 🚀 Ready for deployment with Mailgun email delivery!
pause
