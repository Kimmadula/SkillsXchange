<?php
/**
 * Quick Skill Addition Script
 * Access this via: https://your-domain.com/add-test-skills.php
 * This will add some test skills to your database
 */

// Include Laravel bootstrap
require_once '../vendor/autoload.php';

$app = require_once '../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Skill;
use Illuminate\Support\Facades\DB;

?>
<!DOCTYPE html>
<html>
<head>
    <title>SkillsXchange - Add Test Skills</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .error { background: #fef2f2; color: #dc2626; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .info { background: #f0f9ff; color: #0c4a6e; padding: 15px; border-radius: 8px; margin: 20px 0; }
        button { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin: 5px; }
        button:hover { background: #2563eb; }
        .skill-list { background: #f9fafb; padding: 15px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🎯 Add Test Skills</h1>
    
    <?php
    try {
        // Check current skills
        $currentSkills = Skill::all();
        echo "<div class='info'>";
        echo "<h3>📊 Current Skills Status:</h3>";
        echo "<p><strong>Total Skills:</strong> " . $currentSkills->count() . "</p>";
        echo "<p><strong>Categories:</strong> " . $currentSkills->groupBy('category')->count() . "</p>";
        echo "</div>";

        // Add test skills if requested
        if (isset($_POST['add_skills'])) {
            echo "<div class='info'>Adding test skills...</div>";
            
            $testSkills = [
                // IT Skills
                ['name' => 'Web Development', 'category' => 'IT'],
                ['name' => 'Mobile App Development', 'category' => 'IT'],
                ['name' => 'Database Management', 'category' => 'IT'],
                ['name' => 'Cybersecurity', 'category' => 'IT'],
                ['name' => 'Cloud Computing', 'category' => 'IT'],
                ['name' => 'Data Analysis', 'category' => 'IT'],
                
                // Design Skills
                ['name' => 'Graphic Design', 'category' => 'Design'],
                ['name' => 'UI/UX Design', 'category' => 'Design'],
                ['name' => 'Video Editing', 'category' => 'Design'],
                ['name' => 'Photography', 'category' => 'Design'],
                ['name' => '3D Modeling', 'category' => 'Design'],
                
                // Business Skills
                ['name' => 'Digital Marketing', 'category' => 'Business'],
                ['name' => 'Project Management', 'category' => 'Business'],
                ['name' => 'Financial Analysis', 'category' => 'Business'],
                ['name' => 'Sales Strategy', 'category' => 'Business'],
                ['name' => 'Content Writing', 'category' => 'Business'],
                
                // Languages
                ['name' => 'English Tutoring', 'category' => 'Languages'],
                ['name' => 'Spanish', 'category' => 'Languages'],
                ['name' => 'Japanese', 'category' => 'Languages'],
                ['name' => 'French', 'category' => 'Languages'],
                
                // Culinary
                ['name' => 'Cooking', 'category' => 'Culinary'],
                ['name' => 'Baking', 'category' => 'Culinary'],
                ['name' => 'Food Photography', 'category' => 'Culinary'],
                
                // Arts & Crafts
                ['name' => 'Painting', 'category' => 'Arts'],
                ['name' => 'Music Production', 'category' => 'Arts'],
                ['name' => 'Guitar Playing', 'category' => 'Arts'],
                ['name' => 'Pottery', 'category' => 'Arts'],
                
                // Fitness & Health
                ['name' => 'Personal Training', 'category' => 'Fitness'],
                ['name' => 'Yoga Instruction', 'category' => 'Fitness'],
                ['name' => 'Nutrition Counseling', 'category' => 'Fitness'],
                
                // Academic
                ['name' => 'Mathematics Tutoring', 'category' => 'Academic'],
                ['name' => 'Science Tutoring', 'category' => 'Academic'],
                ['name' => 'Essay Writing', 'category' => 'Academic'],
                ['name' => 'Research Methods', 'category' => 'Academic'],
            ];
            
            $addedCount = 0;
            $skippedCount = 0;
            
            foreach ($testSkills as $skillData) {
                // Check if skill already exists
                $existing = Skill::where('name', $skillData['name'])->first();
                
                if (!$existing) {
                    Skill::create($skillData);
                    $addedCount++;
                    echo "<p>✅ Added: {$skillData['name']} ({$skillData['category']})</p>";
                } else {
                    $skippedCount++;
                    echo "<p>⏭️ Skipped (exists): {$skillData['name']}</p>";
                }
            }
            
            echo "<div class='success'>";
            echo "<h3>✅ Skills Addition Complete!</h3>";
            echo "<p><strong>Added:</strong> {$addedCount} new skills</p>";
            echo "<p><strong>Skipped:</strong> {$skippedCount} existing skills</p>";
            echo "</div>";
        }
        
        // Display current skills
        if ($currentSkills->count() > 0) {
            echo "<div class='skill-list'>";
            echo "<h3>📋 Current Skills by Category:</h3>";
            
            foreach ($currentSkills->groupBy('category') as $category => $skills) {
                echo "<h4>{$category} ({$skills->count()} skills)</h4>";
                echo "<ul>";
                foreach ($skills as $skill) {
                    echo "<li>{$skill->name}</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }
        
        // Add skills form
        if ($currentSkills->count() < 10) {
            echo "<div class='info'>";
            echo "<h3>🚀 Add Test Skills</h3>";
            echo "<p>Click the button below to add a comprehensive set of test skills to your database.</p>";
            echo "<form method='POST'>";
            echo "<button type='submit' name='add_skills'>Add Test Skills</button>";
            echo "</form>";
            echo "</div>";
        } else {
            echo "<div class='success'>";
            echo "<h3>✅ Skills Available</h3>";
            echo "<p>You have enough skills in your database. The skill selection should now work properly!</p>";
            echo "</div>";
        }
        
        // Test links
        echo "<div class='info'>";
        echo "<h3>🔗 Test Links</h3>";
        echo "<p>Now test the skill selection:</p>";
        echo "<a href='/trades/create' target='_blank'><button>Test Trade Creation</button></a>";
        echo "<a href='/admin/skills' target='_blank'><button>Admin Skills Panel</button></a>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Error:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    ?>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p><strong>Security Note:</strong> Delete this file after adding skills for security reasons.</p>
        <p><strong>File location:</strong> public/add-test-skills.php</p>
    </div>
</body>
</html>
