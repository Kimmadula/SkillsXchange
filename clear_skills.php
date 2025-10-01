<?php
/**
 * Simple script to clear all skills from the database
 * Run this script directly: php clear_skills.php
 */

// Include Laravel's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Skill;

echo "Clearing all skills from the database...\n";

try {
    $skillCount = Skill::count();
    
    if ($skillCount === 0) {
        echo "No skills found in the database.\n";
        exit(0);
    }

    echo "Found {$skillCount} skills to delete.\n";
    echo "Are you sure you want to delete all skills? (y/N): ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) === 'y' || trim(strtolower($line)) === 'yes') {
        Skill::truncate();
        echo "Successfully deleted all {$skillCount} skills.\n";
    } else {
        echo "Operation cancelled.\n";
    }

} catch (Exception $e) {
    echo "Error clearing skills: " . $e->getMessage() . "\n";
    exit(1);
}
