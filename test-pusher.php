<?php
/**
 * Simple Pusher Test Script
 * Run this to test if Pusher broadcasting is working
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Pusher\Pusher;

// Pusher configuration
$pusher = new Pusher(
    $_ENV['PUSHER_APP_KEY'],
    $_ENV['PUSHER_APP_SECRET'],
    $_ENV['PUSHER_APP_ID'],
    [
        'cluster' => $_ENV['PUSHER_APP_CLUSTER'],
        'useTLS' => $_ENV['PUSHER_USE_TLS'] ?? false,
        'encrypted' => $_ENV['PUSHER_ENCRYPTED'] ?? false
    ]
);

echo "Testing Pusher connection...\n";
echo "App ID: " . $_ENV['PUSHER_APP_ID'] . "\n";
echo "App Key: " . $_ENV['PUSHER_APP_KEY'] . "\n";
echo "Cluster: " . $_ENV['PUSHER_APP_CLUSTER'] . "\n\n";

try {
    // Test message
    $data = [
        'message' => 'Test message from PHP',
        'timestamp' => date('Y-m-d H:i:s'),
        'test' => true
    ];
    
    $result = $pusher->trigger('test-channel', 'test-event', $data);
    
    if ($result) {
        echo "✅ Pusher test successful!\n";
        echo "Message sent to channel: test-channel\n";
        echo "Event: test-event\n";
        echo "Data: " . json_encode($data) . "\n";
    } else {
        echo "❌ Pusher test failed - no result returned\n";
    }
    
} catch (Exception $e) {
    echo "❌ Pusher test failed with error: " . $e->getMessage() . "\n";
}

echo "\nTo test in browser, open browser console and run:\n";
echo "var pusher = new Pusher('" . $_ENV['PUSHER_APP_KEY'] . "', { cluster: '" . $_ENV['PUSHER_APP_CLUSTER'] . "' });\n";
echo "var channel = pusher.subscribe('test-channel');\n";
echo "channel.bind('test-event', function(data) { console.log('Received:', data); });\n";
