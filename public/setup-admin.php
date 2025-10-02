<?php
/**
 * Admin Setup Page
 * Access this via: https://your-domain.com/setup-admin.php
 * This will create an admin user directly in your database
 */

// Include Laravel bootstrap
require_once '../vendor/autoload.php';

$app = require_once '../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

?>
<!DOCTYPE html>
<html>
<head>
    <title>SkillsXchange - Admin Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .error { background: #fef2f2; color: #dc2626; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .info { background: #f0f9ff; color: #0c4a6e; padding: 15px; border-radius: 8px; margin: 20px 0; }
        button { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
        button:hover { background: #2563eb; }
        .admin-info { background: #f9fafb; padding: 15px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔧 SkillsXchange Admin Setup</h1>
    
    <?php
    try {
        // Check if setup was requested
        if (isset($_POST['setup_admin'])) {
            echo "<div class='info'>Setting up admin user...</div>";
            
            // Check if admin already exists
            $existingAdmin = User::where('role', 'admin')->first();
            
            if ($existingAdmin) {
                echo "<div class='success'>✅ Admin user already exists: {$existingAdmin->email}</div>";
            } else {
                // Create admin user
                $admin = User::create([
                    'firstname' => 'System',
                    'lastname' => 'Admin',
                    'username' => 'admin',
                    'email' => 'admin@skillsxchange.com',
                    'password' => Hash::make('admin123'),
                    'role' => 'admin',
                    'is_verified' => true,
                    'plan' => 'premium',
                    'token_balance' => 100,
                ]);
                
                echo "<div class='success'>✅ Admin user created successfully!</div>";
            }
            
            // Get admin info
            $admin = User::where('role', 'admin')->first();
            echo "<div class='admin-info'>";
            echo "<h3>Admin Login Details:</h3>";
            echo "<p><strong>Email:</strong> {$admin->email}</p>";
            echo "<p><strong>Password:</strong> admin123</p>";
            echo "<p><strong>Role:</strong> {$admin->role}</p>";
            echo "<p><strong>Verified:</strong> " . ($admin->is_verified ? 'Yes' : 'No') . "</p>";
            echo "</div>";
        }
        
        // Database connection test
        $usersCount = DB::table('users')->count();
        $skillsCount = DB::table('skills')->count();
        
        echo "<div class='info'>";
        echo "<h3>📊 Database Status:</h3>";
        echo "<p>✅ Database connection: Working</p>";
        echo "<p>👥 Total users: {$usersCount}</p>";
        echo "<p>🎯 Total skills: {$skillsCount}</p>";
        echo "</div>";
        
        // Check for existing admin
        $adminExists = User::where('role', 'admin')->exists();
        
        if (!$adminExists) {
            echo "<div class='error'>";
            echo "<h3>⚠️ No Admin User Found</h3>";
            echo "<p>Click the button below to create an admin user.</p>";
            echo "</div>";
            
            echo "<form method='POST'>";
            echo "<button type='submit' name='setup_admin'>Create Admin User</button>";
            echo "</form>";
        } else {
            $admin = User::where('role', 'admin')->first();
            echo "<div class='success'>";
            echo "<h3>✅ Admin User Exists</h3>";
            echo "<p>Admin email: {$admin->email}</p>";
            echo "<p>You can now log in and manage skills!</p>";
            echo "</div>";
            
            echo "<div class='admin-info'>";
            echo "<h3>🚀 Next Steps:</h3>";
            echo "<ol>";
            echo "<li>Go to your main application</li>";
            echo "<li>Log in with: <strong>{$admin->email}</strong> / <strong>admin123</strong></li>";
            echo "<li>Navigate to: <strong>/admin/skills</strong></li>";
            echo "<li>Start adding and managing skills!</li>";
            echo "</ol>";
            echo "</div>";
        }
        
        // Test skill creation
        if (isset($_POST['test_skill'])) {
            $testSkillName = 'Test Skill ' . time();
            DB::table('skills')->insert([
                'name' => $testSkillName,
                'category' => 'Test Category'
            ]);
            echo "<div class='success'>✅ Test skill created: {$testSkillName}</div>";
            
            // Clean up
            DB::table('skills')->where('name', $testSkillName)->delete();
            echo "<div class='info'>🧹 Test skill cleaned up</div>";
        }
        
        echo "<form method='POST' style='margin-top: 20px;'>";
        echo "<button type='submit' name='test_skill'>Test Skill Creation</button>";
        echo "</form>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Error:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    ?>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p><strong>Security Note:</strong> Delete this file after setup is complete for security reasons.</p>
        <p><strong>File location:</strong> public/setup-admin.php</p>
    </div>
</body>
</html>
