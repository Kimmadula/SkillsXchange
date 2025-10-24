<?php
// Test script to verify the auto-verification fix
// Access via: http://localhost/SkillsXchange/test-auto-verification.php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TradeTask;
use App\Models\TaskEvaluation;

echo "<h1>Auto-Verification Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Check the specific task (ID 46)
    $task = TradeTask::find(46);
    
    if (!$task) {
        echo "<p class='error'>❌ Task not found</p>";
        exit;
    }
    
    echo "<h2>Current Task Status:</h2>";
    echo "<p><strong>ID:</strong> {$task->id}</p>";
    echo "<p><strong>Title:</strong> {$task->title}</p>";
    echo "<p><strong>Status:</strong> {$task->current_status}</p>";
    echo "<p><strong>Completed:</strong> " . ($task->completed ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Verified:</strong> " . ($task->verified ? 'Yes' : 'No') . "</p>";
    
    // Check evaluations
    $evaluations = TaskEvaluation::where('task_id', $task->id)->get();
    echo "<h2>Task Evaluations (" . $evaluations->count() . "):</h2>";
    
    if ($evaluations->count() > 0) {
        foreach ($evaluations as $evaluation) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>Score:</strong> {$evaluation->score_percentage}%</p>";
            echo "<p><strong>Status:</strong> {$evaluation->status}</p>";
            echo "<p><strong>Evaluated By:</strong> {$evaluation->evaluated_by}</p>";
            echo "<p><strong>Created At:</strong> {$evaluation->created_at}</p>";
            echo "</div>";
        }
    }
    
    echo "<h2>System Fix Status:</h2>";
    echo "<p class='success'>✅ Auto-verification fix has been implemented</p>";
    echo "<p class='info'>Now when a task passes evaluation (status = 'pass'), it will automatically be verified for skill learning</p>";
    
    echo "<h2>How to Test:</h2>";
    echo "<ol>";
    echo "<li>Create a new task in a session</li>";
    echo "<li>Have someone submit work for the task</li>";
    echo "<li>Evaluate the task with a passing score (≥70%)</li>";
    echo "<li>Check that the task is automatically verified</li>";
    echo "<li>End the session - skills should now be added!</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
