<?php
/**
 * Test script to verify the ratings API is working
 * Run this from your browser: https://your-domain.com/test-ratings-api.php
 */

// Test the API endpoint directly
$userId = 36; // The user ID from your data
$apiUrl = "/api/user-ratings/{$userId}";

echo "<h2>Testing Ratings API</h2>";
echo "<p><strong>API URL:</strong> {$apiUrl}</p>";
echo "<p><strong>User ID:</strong> {$userId}</p>";

// Test the API call
$fullUrl = "https://" . $_SERVER['HTTP_HOST'] . $apiUrl;
echo "<p><strong>Full URL:</strong> <a href='{$fullUrl}' target='_blank'>{$fullUrl}</a></p>";

// Make the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>API Response</h3>";
echo "<p><strong>HTTP Status:</strong> {$httpCode}</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Parse and display the response
$data = json_decode($response, true);
if ($data && isset($data['success'])) {
    if ($data['success']) {
        echo "<h3>✅ API Working!</h3>";
        echo "<p>Found " . count($data['ratings']) . " ratings</p>";
        
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
            echo "</ul>";
        }
    } else {
        echo "<h3>❌ API Error</h3>";
        echo "<p>Message: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<h3>❌ Invalid Response</h3>";
    echo "<p>Could not parse JSON response</p>";
}
?>
