<?php

/**
 * Clear Rate Limiting Cache
 * 
 * This script clears all rate limiting cache to reset login attempts.
 * Run this when you're getting rate limited during testing.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧹 Clearing rate limiting cache...\n";

try {
    // Clear all rate limiting cache
    $keys = Cache::getRedis()->keys('*rate*');
    if (!empty($keys)) {
        Cache::getRedis()->del($keys);
        echo "✅ Cleared " . count($keys) . " rate limiting cache entries\n";
    } else {
        echo "ℹ️  No rate limiting cache entries found\n";
    }
    
    // Clear Laravel's rate limiter cache
    $rateLimitKeys = Cache::getRedis()->keys('*throttle*');
    if (!empty($rateLimitKeys)) {
        Cache::getRedis()->del($rateLimitKeys);
        echo "✅ Cleared " . count($rateLimitKeys) . " throttle cache entries\n";
    }
    
    // Clear any login-specific cache
    $loginKeys = Cache::getRedis()->keys('*login*');
    if (!empty($loginKeys)) {
        Cache::getRedis()->del($loginKeys);
        echo "✅ Cleared " . count($loginKeys) . " login cache entries\n";
    }
    
    echo "\n🎉 Rate limiting cache cleared successfully!\n";
    echo "You can now try logging in again.\n";
    
} catch (Exception $e) {
    echo "❌ Error clearing cache: " . $e->getMessage() . "\n";
    echo "Try running: php artisan cache:clear\n";
}
