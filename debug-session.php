<?php
// Debug script to check session completion and skill learning
// Place this in your project root and run: php debug-session.php

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Trade;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\SessionRating;
use App\Models\SkillAcquisitionHistory;

// Replace with your user ID
$userId = 1; // Change this to your user ID

echo "=== DEBUGGING SESSION COMPLETION ===\n\n";

// Check user
$user = User::find($userId);
if (!$user) {
    echo "❌ User not found with ID: $userId\n";
    exit;
}

echo "✅ User found: {$user->firstname} {$user->lastname}\n\n";

// Check user skills
$userSkills = UserSkill::where('user_id', $userId)->get();
echo "📊 User Skills Count: " . $userSkills->count() . "\n";
foreach ($userSkills as $skill) {
    echo "  - Skill ID: {$skill->skill_id}\n";
}

// Check skill acquisition history
$acquisitionHistory = SkillAcquisitionHistory::where('user_id', $userId)->get();
echo "\n📈 Skill Acquisition History Count: " . $acquisitionHistory->count() . "\n";
foreach ($acquisitionHistory as $acquisition) {
    echo "  - Skill ID: {$acquisition->skill_id}, Method: {$acquisition->acquisition_method}, Score: {$acquisition->score_achieved}\n";
}

// Check session ratings
$ratings = SessionRating::where('rated_user_id', $userId)->get();
echo "\n⭐ Session Ratings Count: " . $ratings->count() . "\n";
foreach ($ratings as $rating) {
    echo "  - Overall: {$rating->overall_rating}, Communication: {$rating->communication_rating}\n";
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

echo "\n🔄 Recent Closed Trades: " . $recentTrades->count() . "\n";
foreach ($recentTrades as $trade) {
    echo "  - Trade ID: {$trade->id}, Status: {$trade->status}, Updated: {$trade->updated_at}\n";
    
    // Check tasks for this trade
    $tasks = $trade->tasks()->where('assigned_to', $userId)->get();
    $completedTasks = $tasks->where('completed', true)->where('verified', true);
    $completionRate = $tasks->count() > 0 ? ($completedTasks->count() / $tasks->count()) * 100 : 0;
    
    echo "    Tasks: {$tasks->count()}, Completed & Verified: {$completedTasks->count()}, Rate: " . round($completionRate, 2) . "%\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
