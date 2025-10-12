<?php
/**
 * Test Resend Configuration
 * 
 * This script tests if the Resend configuration is properly set up
 * and can send emails using the Resend API.
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo "🔧 Testing Resend Configuration...\n\n";

// Test 1: Check if Resend config is loaded
echo "1. Checking Resend configuration...\n";
$resendApiKey = config('resend.api_key');
if ($resendApiKey) {
    echo "   ✅ Resend API key found: " . substr($resendApiKey, 0, 10) . "...\n";
} else {
    echo "   ❌ Resend API key not found!\n";
    exit(1);
}

// Test 2: Check mail configuration
echo "\n2. Checking mail configuration...\n";
$mailDriver = config('mail.default');
$mailFromAddress = config('mail.from.address');
$mailFromName = config('mail.from.name');

echo "   Mail driver: $mailDriver\n";
echo "   From address: $mailFromAddress\n";
echo "   From name: $mailFromName\n";

if ($mailDriver === 'resend') {
    echo "   ✅ Mail driver is set to 'resend'\n";
} else {
    echo "   ❌ Mail driver is not set to 'resend'\n";
}

// Test 3: Test sending a simple email
echo "\n3. Testing email sending...\n";
try {
    $testEmail = 'asdtumakay@gmail.com';
    
    Mail::raw('This is a test email from SkillsXchange to verify Resend configuration is working properly.', function ($message) use ($testEmail, $mailFromAddress, $mailFromName) {
        $message->to($testEmail)
                ->subject('Resend Configuration Test - ' . date('Y-m-d H:i:s'))
                ->from($mailFromAddress, $mailFromName);
    });
    
    echo "   ✅ Email sent successfully to $testEmail\n";
    echo "   📧 Check your email inbox for the test message\n";
    
} catch (Exception $e) {
    echo "   ❌ Error sending email: " . $e->getMessage() . "\n";
    echo "   📋 Full error details:\n";
    echo "   " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 Configuration test completed!\n";
echo "\n📋 Summary:\n";
echo "   - Resend API key: " . (config('resend.api_key') ? '✅ Configured' : '❌ Missing') . "\n";
echo "   - Mail driver: " . (config('mail.default') === 'resend' ? '✅ Set to resend' : '❌ Not set to resend') . "\n";
echo "   - From address: " . config('mail.from.address') . "\n";
echo "   - From name: " . config('mail.from.name') . "\n";
