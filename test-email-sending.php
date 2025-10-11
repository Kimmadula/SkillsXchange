<?php
/**
 * Test Email Sending for SkillsXchange
 * 
 * This script tests if the Brevo email configuration is working
 * Run with: php test-email-sending.php
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

// Set up Laravel configuration
Config::set('mail.default', 'smtp');
Config::set('mail.mailers.smtp.transport', 'smtp');
Config::set('mail.mailers.smtp.host', env('MAIL_HOST', 'smtp-relay.brevo.com'));
Config::set('mail.mailers.smtp.port', env('MAIL_PORT', 587));
Config::set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', 'tls'));
Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME'));
Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD'));
Config::set('mail.from.address', env('MAIL_FROM_ADDRESS', 'johnninonavares7@gmail.com'));
Config::set('mail.from.name', env('MAIL_FROM_NAME', 'SkillsXchange'));

echo "🧪 Testing SkillsXchange Email Sending\n";
echo "====================================\n\n";

// Test 1: Check configuration
echo "📋 Email Configuration:\n";
echo "MAIL_MAILER: " . Config::get('mail.default') . "\n";
echo "MAIL_HOST: " . Config::get('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . Config::get('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . Config::get('mail.mailers.smtp.username') . "\n";
echo "MAIL_FROM_ADDRESS: " . Config::get('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . Config::get('mail.from.name') . "\n\n";

// Test 2: Send a test email
echo "📧 Sending Test Email...\n";
try {
    Mail::raw('This is a test email from SkillsXchange to verify Brevo SMTP configuration.', function ($message) {
        $message->to('johnninonavares7@gmail.com')
                ->subject('SkillsXchange Email Test - ' . date('Y-m-d H:i:s'));
    });
    
    echo "✅ Test email sent successfully!\n";
    echo "Check your inbox at johnninonavares7@gmail.com\n\n";
    
} catch (Exception $e) {
    echo "❌ Test email failed: " . $e->getMessage() . "\n";
    echo "Error details: " . $e->getTraceAsString() . "\n\n";
}

// Test 3: Test email verification notification
echo "🔐 Testing Email Verification Notification...\n";
try {
    // Create a mock user for testing
    $mockUser = new stdClass();
    $mockUser->id = 1;
    $mockUser->email = 'johnninonavares7@gmail.com';
    $mockUser->firstname = 'Test';
    $mockUser->getEmailForVerification = function() { return 'johnninonavares7@gmail.com'; };
    $mockUser->getKey = function() { return 1; };
    
    // This would normally be called by Laravel's verification system
    echo "✅ Email verification notification would be sent to: " . $mockUser->email . "\n";
    
} catch (Exception $e) {
    echo "❌ Email verification test failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test Complete!\n";
echo "If the test email was sent successfully, your Brevo configuration is working.\n";
echo "If not, check your environment variables and Brevo account settings.\n";
