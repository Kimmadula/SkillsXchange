<?php
/**
 * SkillsXchange Email Configuration Test
 * 
 * This script tests the Brevo email configuration
 * Run with: php test-email-config.php
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

// Set up basic Laravel configuration
Config::set('mail.default', 'smtp');
Config::set('mail.mailers.smtp.transport', 'smtp');
Config::set('mail.mailers.smtp.host', env('MAIL_HOST', 'smtp-relay.brevo.com'));
Config::set('mail.mailers.smtp.port', env('MAIL_PORT', 587));
Config::set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', 'tls'));
Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME'));
Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD'));
Config::set('mail.from.address', env('MAIL_FROM_ADDRESS', 'johnninonavares7@gmail.com'));
Config::set('mail.from.name', env('MAIL_FROM_NAME', 'SkillsXchange'));

echo "🧪 Testing SkillsXchange Email Configuration\n";
echo "==========================================\n\n";

// Test 1: Check environment variables
echo "📋 Environment Variables Check:\n";
echo "MAIL_HOST: " . (env('MAIL_HOST') ?: 'NOT SET') . "\n";
echo "MAIL_PORT: " . (env('MAIL_PORT') ?: 'NOT SET') . "\n";
echo "MAIL_USERNAME: " . (env('MAIL_USERNAME') ?: 'NOT SET') . "\n";
echo "MAIL_PASSWORD: " . (env('MAIL_PASSWORD') ? '***SET***' : 'NOT SET') . "\n";
echo "MAIL_FROM_ADDRESS: " . (env('MAIL_FROM_ADDRESS') ?: 'NOT SET') . "\n";
echo "MAIL_FROM_NAME: " . (env('MAIL_FROM_NAME') ?: 'NOT SET') . "\n\n";

// Test 2: Check Pusher configuration
echo "📡 Pusher Configuration Check:\n";
echo "PUSHER_APP_ID: " . (env('PUSHER_APP_ID') ?: 'NOT SET') . "\n";
echo "PUSHER_APP_KEY: " . (env('PUSHER_APP_KEY') ?: 'NOT SET') . "\n";
echo "PUSHER_APP_SECRET: " . (env('PUSHER_APP_SECRET') ? '***SET***' : 'NOT SET') . "\n";
echo "PUSHER_APP_CLUSTER: " . (env('PUSHER_APP_CLUSTER') ?: 'NOT SET') . "\n\n";

// Test 3: Test SMTP connection
echo "🔌 Testing SMTP Connection:\n";
try {
    $transport = new \Swift_SmtpTransport(
        env('MAIL_HOST', 'smtp-relay.brevo.com'),
        env('MAIL_PORT', 587),
        env('MAIL_ENCRYPTION', 'tls')
    );
    
    $transport->setUsername(env('MAIL_USERNAME'));
    $transport->setPassword(env('MAIL_PASSWORD'));
    
    $mailer = new \Swift_Mailer($transport);
    
    // Test connection
    $mailer->getTransport()->start();
    echo "✅ SMTP connection successful!\n";
    $mailer->getTransport()->stop();
    
} catch (Exception $e) {
    echo "❌ SMTP connection failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Configuration Summary:\n";
echo "========================\n";

$emailConfigured = env('MAIL_HOST') && env('MAIL_USERNAME') && env('MAIL_PASSWORD');
$pusherConfigured = env('PUSHER_APP_ID') && env('PUSHER_APP_KEY') && env('PUSHER_APP_SECRET');

echo "Email Configuration: " . ($emailConfigured ? "✅ READY" : "❌ INCOMPLETE") . "\n";
echo "Pusher Configuration: " . ($pusherConfigured ? "✅ READY" : "❌ INCOMPLETE") . "\n";

if ($emailConfigured && $pusherConfigured) {
    echo "\n🚀 DEPLOYMENT READY!\n";
    echo "Your SkillsXchange application is configured for production deployment.\n";
} else {
    echo "\n⚠️  CONFIGURATION INCOMPLETE\n";
    echo "Please check your environment variables and try again.\n";
}

echo "\n📚 Next Steps:\n";
echo "1. Copy env.template to .env for local development\n";
echo "2. Update Render environment variables with the values from render.env\n";
echo "3. Deploy to Render and test email functionality\n";
echo "4. Test real-time features with Pusher\n";
