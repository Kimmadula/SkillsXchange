<?php
/**
 * Debug script for Ratings API issues
 * Run this from your browser: https://your-domain.com/debug-ratings.php
 */

echo "<h1>🔍 Ratings API Debug Report</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Test 1: Check if Laravel is working
echo "<h2>Test 1: Laravel Framework Check</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    echo "✅ Laravel framework loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Laravel framework failed to load: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST', '127.0.0.1') . 
        ";port=" . env('DB_PORT', '3306') . 
        ";dbname=" . env('DB_DATABASE', 'skillsxchangee'),
        env('DB_USERNAME', 'root'),
        env('DB_PASSWORD', '')
    );
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check if session_ratings table exists
echo "<h2>Test 3: Database Tables</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'session_ratings'")->fetchAll();
    if (count($tables) > 0) {
        echo "✅ session_ratings table exists<br>";
        
        // Check table structure
        $columns = $pdo->query("DESCRIBE session_ratings")->fetchAll();
        echo "<strong>Table structure:</strong><br>";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
    } else {
        echo "❌ session_ratings table does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
}

// Test 4: Check for ratings data
echo "<h2>Test 4: Ratings Data</h2>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM session_ratings");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✅ Found " . $result['count'] . " ratings in database<br>";
    
    if ($result['count'] > 0) {
        // Get sample data
        $stmt = $pdo->prepare("SELECT * FROM session_ratings ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $rating = $stmt->fetch();
        
        echo "<strong>Sample rating data:</strong><br>";
        echo "- ID: " . $rating['id'] . "<br>";
        echo "- Rated User ID: " . $rating['rated_user_id'] . "<br>";
        echo "- Rater ID: " . $rating['rater_id'] . "<br>";
        echo "- Overall Rating: " . $rating['overall_rating'] . "<br>";
        echo "- Session Type: " . $rating['session_type'] . "<br>";
        echo "- Created: " . $rating['created_at'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking ratings data: " . $e->getMessage() . "<br>";
}

// Test 5: Check if users table exists and has data
echo "<h2>Test 5: Users Table</h2>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✅ Found " . $result['count'] . " users in database<br>";
    
    // Check if user ID 36 exists
    $stmt = $pdo->prepare("SELECT id, firstname, lastname, username FROM users WHERE id = 36");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User ID 36 exists: " . $user['firstname'] . " " . $user['lastname'] . " (@" . $user['username'] . ")<br>";
    } else {
        echo "❌ User ID 36 does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking users: " . $e->getMessage() . "<br>";
}

// Test 6: Test the API endpoint directly
echo "<h2>Test 6: API Endpoint Test</h2>";
$userId = 36;
$apiUrl = "https://" . $_SERVER['HTTP_HOST'] . "/api/user-ratings/{$userId}";

echo "<strong>Testing API URL:</strong> <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a><br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: Debug-Script/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<strong>HTTP Status:</strong> {$httpCode}<br>";
if ($error) {
    echo "<strong>CURL Error:</strong> {$error}<br>";
}

if ($response) {
    echo "<strong>Response:</strong><br>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    // Try to parse JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "<strong>Parsed JSON:</strong><br>";
        if (isset($data['success'])) {
            if ($data['success']) {
                echo "✅ API returned success: true<br>";
                echo "✅ Found " . count($data['ratings']) . " ratings<br>";
            } else {
                echo "❌ API returned success: false<br>";
                echo "❌ Error message: " . ($data['message'] ?? 'Unknown error') . "<br>";
            }
        } else {
            echo "⚠️ API response missing 'success' field<br>";
        }
    } else {
        echo "❌ Response is not valid JSON<br>";
    }
} else {
    echo "❌ No response received from API<br>";
}

// Test 7: Check Laravel routes
echo "<h2>Test 7: Laravel Routes</h2>";
try {
    $routes = app('router')->getRoutes();
    $ratingRoutes = [];
    
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'rating') !== false || strpos($route->uri(), 'user-ratings') !== false) {
            $ratingRoutes[] = [
                'uri' => $route->uri(),
                'methods' => implode(',', $route->methods()),
                'name' => $route->getName()
            ];
        }
    }
    
    if (count($ratingRoutes) > 0) {
        echo "✅ Found " . count($ratingRoutes) . " rating-related routes:<br>";
        foreach ($ratingRoutes as $route) {
            echo "- " . $route['methods'] . " " . $route['uri'] . " (name: " . $route['name'] . ")<br>";
        }
    } else {
        echo "❌ No rating-related routes found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking routes: " . $e->getMessage() . "<br>";
}

// Test 8: Check environment variables
echo "<h2>Test 8: Environment Variables</h2>";
$envVars = [
    'APP_ENV' => env('APP_ENV'),
    'APP_DEBUG' => env('APP_DEBUG'),
    'DB_CONNECTION' => env('DB_CONNECTION'),
    'DB_HOST' => env('DB_HOST'),
    'DB_DATABASE' => env('DB_DATABASE'),
    'RATING_FEATURE_ENABLED' => env('RATING_FEATURE_ENABLED', 'not set')
];

foreach ($envVars as $key => $value) {
    echo "<strong>{$key}:</strong> " . ($value ?: 'not set') . "<br>";
}

// Test 9: Check if SessionRating model exists
echo "<h2>Test 9: Model Check</h2>";
try {
    if (class_exists('App\Models\SessionRating')) {
        echo "✅ SessionRating model exists<br>";
        
        // Try to create an instance
        $model = new App\Models\SessionRating();
        echo "✅ SessionRating model can be instantiated<br>";
        
        // Check if we can query
        $count = App\Models\SessionRating::count();
        echo "✅ SessionRating model query successful: {$count} records<br>";
    } else {
        echo "❌ SessionRating model does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error with SessionRating model: " . $e->getMessage() . "<br>";
}

// Test 10: Check file permissions
echo "<h2>Test 10: File Permissions</h2>";
$files = [
    'app/Http/Controllers/SessionRatingController.php',
    'routes/web.php',
    'app/Models/SessionRating.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $readable = is_readable($file) ? '✅' : '❌';
        echo "{$readable} {$file} (permissions: " . substr(sprintf('%o', $perms), -4) . ")<br>";
    } else {
        echo "❌ {$file} does not exist<br>";
    }
}

echo "<hr>";
echo "<h2>🎯 Summary</h2>";
echo "<p>This debug report should help identify what's causing the ratings API to fail.</p>";
echo "<p><strong>Common issues:</strong></p>";
echo "<ul>";
echo "<li>Database connection problems</li>";
echo "<li>Missing database tables</li>";
echo "<li>Route configuration issues</li>";
echo "<li>Model or controller errors</li>";
echo "<li>Environment variable problems</li>";
echo "</ul>";
?>
