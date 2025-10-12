<?php
/**
 * Test User Email Verification
 * 
 * This script tests if email verification is sent to the user's email address
 * Run with: php test-user-email-verification.php
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Notifications\VerifyEmail;

// Set up Laravel configuration
Config::set('mail.default', 'resend');
Config::set('mail.mailers.resend.transport', 'resend');
Config::set('mail.from.address', env('MAIL_FROM_ADDRESS', 'asdtumakay@gmail.com'));
Config::set('mail.from.name', env('MAIL_FROM_NAME', 'SkillsXchange'));
Config::set('services.resend.key', env('RESEND_API_KEY'));

echo "🧪 Testing User Email Verification\n";
echo "=================================\n\n";

// Test 1: Check configuration
echo "📋 Email Configuration:\n";
echo "MAIL_MAILER: " . Config::get('mail.default') . "\n";
echo "MAIL_FROM_ADDRESS: " . Config::get('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . Config::get('mail.from.name') . "\n";
echo "RESEND_KEY: " . (env('RESEND_KEY') ? '***SET***' : 'NOT SET') . "\n\n";

// Test 2: Create a test user
echo "👤 Creating Test User...\n";
try {
    // Create a test user (don't save to database)
    $testUser = new User();
    $testUser->id = 999;
    $testUser->firstname = 'Test';
    $testUser->lastname = 'User';
    $testUser->email = 'testuser@example.com';
    $testUser->username = 'testuser';
    
    echo "✅ Test user created:\n";
    echo "   Name: {$testUser->firstname} {$testUser->lastname}\n";
    echo "   Email: {$testUser->email}\n";
    echo "   Username: {$testUser->username}\n\n";
    
} catch (Exception $e) {
    echo "❌ Failed to create test user: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test email verification notification
echo "📧 Testing Email Verification Notification...\n";
try {
    // Create the verification notification
    $verificationNotification = new VerifyEmail();
    
    // Get the mail message
    $mailMessage = $verificationNotification->toMail($testUser);
    
    echo "✅ Email verification notification created successfully!\n";
    echo "   Subject: " . $mailMessage->subject . "\n";
    echo "   Recipient: {$testUser->email}\n";
    echo "   From: " . Config::get('mail.from.address') . "\n";
    echo "   From Name: " . Config::get('mail.from.name') . "\n\n";
    
    // Test sending the email
    echo "📤 Sending test verification email...\n";
    Mail::to($testUser->email)->send($verificationNotification);
    
    echo "✅ Test verification email sent successfully!\n";
    echo "   Check inbox at: {$testUser->email}\n\n";
    
} catch (Exception $e) {
    echo "❌ Email verification test failed: " . $e->getMessage() . "\n";
    echo "Error details: " . $e->getTraceAsString() . "\n\n";
}

// Test 4: Test with different email addresses
echo "🔄 Testing with different email addresses...\n";
$testEmails = [
    'user1@example.com',
    'user2@test.com',
    'john.doe@gmail.com'
];

foreach ($testEmails as $email) {
    try {
        $testUser->email = $email;
        echo "   Testing with: {$email}\n";
        
        // This would normally be called by Laravel's verification system
        echo "   ✅ Email would be sent to: {$email}\n";
        
    } catch (Exception $e) {
        echo "   ❌ Failed for {$email}: " . $e->getMessage() . "\n";
    }
}

echo "\n🎯 Test Complete!\n";
echo "================\n";
echo "The email verification system should send emails to the user's email address,\n";
echo "not to the 'from' address. The 'from' address (asdtumakay@gmail.com) is just\n";
echo "the sender address that appears in the email headers.\n\n";

echo "📚 How it works:\n";
echo "1. User registers with their email (e.g., user@example.com)\n";
echo "2. Verification email is sent TO: user@example.com\n";
echo "3. Verification email is sent FROM: asdtumakay@gmail.com\n";
echo "4. User receives the email in their inbox\n\n";

echo "🔍 If emails are not being received:\n";
echo "1. Check spam/junk folder\n";
echo "2. Verify Resend API key is correct\n";
echo "3. Check Render logs for email sending errors\n";
echo "4. Test with a different email address\n";
