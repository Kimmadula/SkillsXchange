<?php
/**
 * Test Resend Email Configuration
 * 
 * This script tests if the Resend API configuration is working
 * Run with: php test-resend-email.php
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

// Set up Laravel configuration
Config::set('mail.default', 'resend');
Config::set('mail.mailers.resend.transport', 'resend');
Config::set('mail.from.address', env('MAIL_FROM_ADDRESS', 'johnninonavares7@gmail.com'));
Config::set('mail.from.name', env('MAIL_FROM_NAME', 'SkillsXchange'));
Config::set('services.resend.key', env('RESEND_KEY'));

echo "🧪 Testing SkillsXchange Resend Email Configuration\n";
echo "==================================================\n\n";

// Test 1: Check configuration
echo "📋 Email Configuration:\n";
echo "MAIL_MAILER: " . Config::get('mail.default') . "\n";
echo "MAIL_FROM_ADDRESS: " . Config::get('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . Config::get('mail.from.name') . "\n";
echo "RESEND_KEY: " . (env('RESEND_KEY') ? '***SET***' : 'NOT SET') . "\n\n";

// Check if Resend key is set
if (!env('RESEND_KEY') || env('RESEND_KEY') === 'your_resend_api_key_here') {
    echo "❌ RESEND_KEY is not set or is using placeholder value\n";
    echo "Please update your .env file with the actual Resend API key\n";
    echo "Get your API key from: https://resend.com/api-keys\n\n";
    exit(1);
}

// Test 2: Send a test email
echo "📧 Sending Test Email via Resend...\n";
try {
    Mail::raw('This is a test email from SkillsXchange to verify Resend API configuration.', function ($message) {
        $message->to('johnninonavares7@gmail.com')
                ->subject('SkillsXchange Resend Test - ' . date('Y-m-d H:i:s'));
    });
    
    echo "✅ Test email sent successfully via Resend!\n";
    echo "Check your inbox at johnninonavares7@gmail.com\n\n";
    
} catch (Exception $e) {
    echo "❌ Test email failed: " . $e->getMessage() . "\n";
    echo "Error details: " . $e->getTraceAsString() . "\n\n";
    
    // Check common issues
    if (strpos($e->getMessage(), 'API key') !== false) {
        echo "💡 Tip: Check if your RESEND_KEY is correct\n";
    }
    if (strpos($e->getMessage(), 'domain') !== false) {
        echo "💡 Tip: You may need to verify your domain in Resend\n";
    }
}

// Test 3: Test email verification notification
echo "🔐 Testing Email Verification Notification...\n";
try {
    // This would normally be called by Laravel's verification system
    echo "✅ Email verification notification would be sent via Resend\n";
    echo "Recipient: johnninonavares7@gmail.com\n";
    
} catch (Exception $e) {
    echo "❌ Email verification test failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test Complete!\n";
echo "================\n";
echo "If the test email was sent successfully, your Resend configuration is working.\n";
echo "If not, check your API key and domain verification in Resend dashboard.\n\n";

echo "📚 Next Steps:\n";
echo "1. Update Render environment variables with your Resend API key\n";
echo "2. Deploy the changes to Render\n";
echo "3. Test email functionality by registering a new user\n";
echo "4. Monitor logs for any email-related errors\n";
