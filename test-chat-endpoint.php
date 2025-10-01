<?php
/**
 * Test Chat Endpoint
 * Simple test to verify the chat endpoint is accessible
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Chat Endpoint...\n";
echo "App URL: " . config('app.url') . "\n";
echo "Trade ID: 1 (assuming)\n\n";

// Test the route
try {
    $url = route('chat.send-message', 1);
    echo "✅ Route generated successfully: " . $url . "\n";
} catch (Exception $e) {
    echo "❌ Route generation failed: " . $e->getMessage() . "\n";
}

// Test CSRF token
try {
    $token = csrf_token();
    echo "✅ CSRF token generated: " . substr($token, 0, 10) . "...\n";
} catch (Exception $e) {
    echo "❌ CSRF token generation failed: " . $e->getMessage() . "\n";
}

// Test if we can access the chat controller
try {
    $controller = new \App\Http\Controllers\ChatController();
    echo "✅ ChatController can be instantiated\n";
} catch (Exception $e) {
    echo "❌ ChatController instantiation failed: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
