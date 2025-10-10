@echo off
REM SkillsXchange Deployment Script with SendPulse Configuration
REM This script prepares your application for deployment with SendPulse email delivery

echo 🚀 SkillsXchange Deployment with SendPulse Setup
echo ===============================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo ❌ Error: Please run this script from the Laravel project root directory
    pause
    exit /b 1
)

echo 📧 Setting up SendPulse for FREE email delivery...
echo.

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
echo 📋 SENDPULSE CONFIGURATION REQUIRED:
echo ====================================
echo.
echo 1. Sign up for SendPulse (FREE): https://sendpulse.com/
echo 2. Get your credentials from: Settings → SMTP & API
echo 3. Update these environment variables in your deployment platform:
echo.
echo    MAIL_MAILER=smtp
echo    MAIL_HOST=smtp.sendpulse.com
echo    MAIL_PORT=587
echo    MAIL_USERNAME=your-sendpulse-email@sendpulse.com
echo    MAIL_PASSWORD=your-sendpulse-password
echo    MAIL_ENCRYPTION=tls
echo    MAIL_FROM_ADDRESS=asdtumakay@gmail.com
echo    MAIL_FROM_NAME=SkillsXchange
echo.

REM Test email configuration if SendPulse is configured
if not "%MAIL_USERNAME%"=="" if not "%MAIL_PASSWORD%"=="" (
    echo 🧪 Testing email configuration...
    php artisan test:email test@example.com
) else (
    echo ⚠️  SendPulse not configured yet. Please set MAIL_USERNAME and MAIL_PASSWORD
)

echo.
echo ✅ Deployment preparation complete!
echo.
echo 🎯 Next steps:
echo    1. Sign up for SendPulse (FREE account)
echo    2. Get SMTP credentials from SendPulse dashboard
echo    3. Configure credentials in your deployment platform
echo    4. Deploy to your hosting platform (Render, Heroku, etc.)
echo    5. Test email delivery after deployment
echo.
echo 💰 SendPulse FREE Plan Benefits:
echo    - 12,000 emails per month
echo    - 1,500 subscribers
echo    - Unlimited campaigns
echo    - SMTP access
echo    - Professional email delivery
echo.
echo 📚 Documentation:
echo    - SendPulse Setup Guide: SENDPULSE_SETUP_GUIDE.md
echo    - Deployment Guide: deployment-guide.md
echo.
echo 🚀 Ready for deployment with FREE SendPulse email delivery!
pause
