<?php
/**
 * Simple test to check if the ratings API is working
 * Run this from your browser: https://your-domain.com/test-ratings-simple.php
 */

echo "<h1>🔍 Simple Ratings API Test</h1>";
echo "<p><strong>Testing API for User ID 36</strong></p>";

$userId = 36;
$apiUrl = "https://" . $_SERVER['HTTP_HOST'] . "/api/user-ratings/{$userId}";

echo "<p><strong>API URL:</strong> <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";

// Test the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Results:</h2>";
echo "<p><strong>HTTP Status:</strong> {$httpCode}</p>";

if ($error) {
    echo "<p><strong>❌ CURL Error:</strong> {$error}</p>";
} else {
    echo "<p><strong>✅ Response received</strong></p>";
    echo "<h3>Raw Response:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    // Try to parse JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "<h3>Parsed JSON:</h3>";
        if (isset($data['success'])) {
            if ($data['success']) {
                echo "<p>✅ <strong>SUCCESS!</strong> Found " . count($data['ratings']) . " ratings</p>";
                
                if (!empty($data['ratings'])) {
                    echo "<h4>Sample Rating:</h4>";
                    $rating = $data['ratings'][0];
                    echo "<ul>";
                    echo "<li>Overall: " . $rating['overall_rating'] . " stars</li>";
                    echo "<li>Communication: " . $rating['communication_rating'] . " stars</li>";
                    echo "<li>Helpfulness: " . $rating['helpfulness_rating'] . " stars</li>";
                    echo "<li>Knowledge: " . $rating['knowledge_rating'] . " stars</li>";
                    echo "<li>Session Type: " . $rating['session_type'] . "</li>";
                    echo "<li>Duration: " . $rating['session_duration'] . " minutes</li>";
                    if (isset($rating['rater'])) {
                        echo "<li>Rater: " . $rating['rater']['name'] . " (@{$rating['rater']['username']})</li>";
                    }
                    echo "</ul>";
                }
            } else {
                echo "<p>❌ <strong>FAILED:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p>⚠️ <strong>WARNING:</strong> API response missing 'success' field</p>";
        }
    } else {
        echo "<p>❌ <strong>ERROR:</strong> Response is not valid JSON</p>";
    }
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Check the browser console for JavaScript errors</li>";
echo "<li>Verify the API endpoint is working (above test)</li>";
echo "<li>Check if the user ID is correct</li>";
echo "<li>Ensure the database has rating data</li>";
echo "</ol>";
?>
