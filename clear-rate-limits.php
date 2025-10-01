<?php

/**
 * Clear Rate Limiting Cache
 * 
 * This script clears the rate limiting cache to resolve login issues.
 * Run this when you're getting rate limited during testing.
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧹 Clearing rate limiting cache...\n";

// Clear all rate limiting cache
$keys = Cache::getRedis()->keys('*rate*');
foreach ($keys as $key) {
    Cache::forget($key);
    echo "Cleared: " . $key . "\n";
}

// Clear specific rate limiting patterns
$patterns = [
    'laravel_cache:*rate*',
    'laravel_cache:*login*',
    'laravel_cache:*auth*',
];

foreach ($patterns as $pattern) {
    $keys = Cache::getRedis()->keys($pattern);
    foreach ($keys as $key) {
        Cache::forget($key);
        echo "Cleared pattern: " . $key . "\n";
    }
}

echo "✅ Rate limiting cache cleared successfully!\n";
echo "You can now try logging in again.\n";
