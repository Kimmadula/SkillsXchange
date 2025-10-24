<?php
// Simple debug script to check profile data
// Access this via: http://localhost/SkillsXchange/debug-profile.php

// Include Laravel bootstrap
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\UserSkill;
use App\Models\SessionRating;
use App\Models\SkillAcquisitionHistory;
use App\Models\Trade;

// Get current user (you'll need to modify this)
$userId = 1; // Change this to your actual user ID

echo "<h1>SkillsXchange Profile Debug</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Check user
    $user = User::find($userId);
    if (!$user) {
        echo "<p class='error'>❌ User not found with ID: $userId</p>";
        exit;
    }
    
    echo "<p class='success'>✅ User found: {$user->firstname} {$user->lastname}</p>";
    
    // Check user skills
    $userSkills = UserSkill::where('user_id', $userId)->get();
    echo "<h2>📊 User Skills (" . $userSkills->count() . ")</h2>";
    if ($userSkills->count() > 0) {
        echo "<ul>";
        foreach ($userSkills as $skill) {
            echo "<li>Skill ID: {$skill->skill_id}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>No skills found</p>";
    }
    
    // Check skill acquisition history
    $acquisitionHistory = SkillAcquisitionHistory::where('user_id', $userId)->get();
    echo "<h2>📈 Skill Acquisition History (" . $acquisitionHistory->count() . ")</h2>";
    if ($acquisitionHistory->count() > 0) {
        echo "<ul>";
        foreach ($acquisitionHistory as $acquisition) {
            echo "<li>Skill ID: {$acquisition->skill_id}, Method: {$acquisition->acquisition_method}, Score: {$acquisition->score_achieved}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>No skill acquisition history found</p>";
    }
    
    // Check session ratings
    $ratings = SessionRating::where('rated_user_id', $userId)->get();
    echo "<h2>⭐ Session Ratings (" . $ratings->count() . ")</h2>";
    if ($ratings->count() > 0) {
        echo "<ul>";
        foreach ($ratings as $rating) {
            echo "<li>Overall: {$rating->overall_rating}, Communication: {$rating->communication_rating}, Date: {$rating->created_at}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>No session ratings found</p>";
    }
    
    // Check recent trades
    $recentTrades = Trade::where('user_id', $userId)
        ->orWhereHas('requests', function($query) use ($userId) {
            $query->where('requester_id', $userId)->where('status', 'accepted');
        })
        ->where('status', 'closed')
        ->orderBy('updated_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "<h2>🔄 Recent Closed Trades (" . $recentTrades->count() . ")</h2>";
    if ($recentTrades->count() > 0) {
        echo "<ul>";
        foreach ($recentTrades as $trade) {
            echo "<li>Trade ID: {$trade->id}, Status: {$trade->status}, Updated: {$trade->updated_at}</li>";
            
            // Check tasks for this trade
            $tasks = $trade->tasks()->where('assigned_to', $userId)->get();
            $completedTasks = $tasks->where('completed', true)->where('verified', true);
            $completionRate = $tasks->count() > 0 ? ($completedTasks->count() / $tasks->count()) * 100 : 0;
            
            echo "<ul><li>Tasks: {$tasks->count()}, Completed & Verified: {$completedTasks->count()}, Rate: " . round($completionRate, 2) . "%</li></ul>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>No closed trades found</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>🔧 Debugging Steps:</h2>";
echo "<ol>";
echo "<li>Check if you have skills in the database</li>";
echo "<li>Check if you have skill acquisition history</li>";
echo "<li>Check if you have session ratings</li>";
echo "<li>Check if your recent trades show 100% completion rate</li>";
echo "<li>If completion rate is less than 100%, your tasks need to be verified</li>";
echo "</ol>";
?>
