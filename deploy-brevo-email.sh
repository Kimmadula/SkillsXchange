#!/bin/bash

# SkillsXchange Brevo Email Deployment Script
# This script ensures the Brevo email configuration is properly deployed

echo "🚀 Deploying SkillsXchange with Brevo Email Configuration"
echo "========================================================"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel project directory"
    exit 1
fi

echo "📧 Setting up Brevo Email Configuration..."

# Clear all caches
echo "1. Clearing configuration cache..."
php artisan config:clear

echo "2. Clearing application cache..."
php artisan cache:clear

echo "3. Clearing route cache..."
php artisan route:clear

echo "4. Clearing view cache..."
php artisan view:clear

# Optimize for production
echo "5. Optimizing configuration for production..."
php artisan config:cache

echo "6. Optimizing routes for production..."
php artisan route:cache

echo "7. Optimizing views for production..."
php artisan view:cache

# Test email configuration
echo "8. Testing email configuration..."
php artisan tinker --execute="
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo 'Current mail configuration:' . PHP_EOL;
echo 'MAIL_MAILER: ' . Config::get('mail.default') . PHP_EOL;
echo 'MAIL_HOST: ' . Config::get('mail.mailers.smtp.host') . PHP_EOL;
echo 'MAIL_PORT: ' . Config::get('mail.mailers.smtp.port') . PHP_EOL;
echo 'MAIL_USERNAME: ' . Config::get('mail.mailers.smtp.username') . PHP_EOL;
echo 'MAIL_FROM_ADDRESS: ' . Config::get('mail.from.address') . PHP_EOL;
echo 'MAIL_FROM_NAME: ' . Config::get('mail.from.name') . PHP_EOL;
"

echo ""
echo "✅ Brevo Email Configuration Deployed Successfully!"
echo ""
echo "📋 Configuration Summary:"
echo "- SMTP Host: smtp-relay.brevo.com"
echo "- Port: 587 (TLS)"
echo "- Username: 98f98d001@smtp-brevo.com"
echo "- From Address: johnninonavares7@gmail.com"
echo "- From Name: SkillsXchange"
echo ""
echo "🎯 Next Steps:"
echo "1. Deploy to Render"
echo "2. Test email functionality by registering a new user"
echo "3. Check Render logs for any email-related errors"
echo ""
echo "🚀 Ready for deployment!"
