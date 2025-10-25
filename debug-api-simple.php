<?php
/**
 * Simple API Debug Script
 * Run this from your browser: https://your-domain.com/debug-api-simple.php
 */

echo "<h1>🔍 Simple API Debug</h1>";

// Test the API endpoint with different user IDs
$userIds = [36, 35, 1]; // Test with the user from your data and others

foreach ($userIds as $userId) {
    echo "<h2>Testing User ID: {$userId}</h2>";
    
    $apiUrl = "https://" . $_SERVER['HTTP_HOST'] . "/api/user-ratings/{$userId}";
    echo "<p><strong>URL:</strong> <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";
    
    // Make the API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Status:</strong> {$httpCode}</p>";
    
    if ($error) {
        echo "<p><strong>❌ CURL Error:</strong> {$error}</p>";
    } else {
        echo "<p><strong>Response:</strong></p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars($response);
        echo "</pre>";
        
        // Parse JSON
        $data = json_decode($response, true);
        if ($data) {
            if (isset($data['success']) && $data['success']) {
                echo "<p>✅ <strong>SUCCESS!</strong> Found " . count($data['ratings']) . " ratings</p>";
            } else {
                echo "<p>❌ <strong>FAILED:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p>❌ <strong>Invalid JSON response</strong></p>";
        }
    }
    
    echo "<hr>";
}

// Test alternative endpoints
echo "<h2>Testing Alternative Endpoints</h2>";

$alternativeEndpoints = [
    "/api/test-ratings/36",
    "/user/36/ratings",
    "/api/user-ratings/36"
];

foreach ($alternativeEndpoints as $endpoint) {
    $fullUrl = "https://" . $_SERVER['HTTP_HOST'] . $endpoint;
    echo "<p><strong>Testing:</strong> <a href='{$fullUrl}' target='_blank'>{$fullUrl}</a></p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>Status: {$httpCode} | Response: " . substr($response, 0, 100) . "...</p>";
    echo "<hr>";
}
?>
