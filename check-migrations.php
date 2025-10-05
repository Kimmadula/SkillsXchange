<?php

// Check if database needs migrations
echo "Checking if database needs migrations...\n";

// Load environment variables from .env file
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$host = $_ENV['DB_HOST'] ?? 'yamanote.proxy.rlwy.net';
$port = $_ENV['DB_PORT'] ?? '45822';
$database = $_ENV['DB_DATABASE'] ?? 'railway';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? 'nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30
    ]);
    
    // Check if migrations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
    $migrationsTable = $stmt->fetch();
    
    if ($migrationsTable) {
        // Check if there are any migrations
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM migrations");
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo "Database has migrations, skipping migration run.\n";
            exit(0);
        } else {
            echo "Database is empty, migrations needed.\n";
            exit(1);
        }
    } else {
        echo "No migrations table found, migrations needed.\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Will skip migrations and continue...\n";
    exit(0);
}
