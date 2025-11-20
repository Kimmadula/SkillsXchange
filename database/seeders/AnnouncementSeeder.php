<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Announcement;
use App\Models\User;
use Carbon\Carbon;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample announcements for testing.
     */
    public function run(): void
    {
        echo "Creating test announcements...\n\n";

        // Get admin user to set as creator
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            echo "WARNING: No admin user found. Using first user as creator.\n";
            $admin = User::first();
        }

        if (!$admin) {
            echo "ERROR: No users found. Please create users first.\n";
            return;
        }

        $now = Carbon::now();

        // 1. Welcome announcement (all users, info, medium priority)
        $announcement1 = Announcement::create([
            'title' => 'Welcome to SkillsXchange!',
            'message' => 'We are excited to have you here! Start trading your skills and learning from others. If you have any questions, feel free to reach out to our support team.',
            'type' => 'info',
            'priority' => 'medium',
            'is_active' => true,
            'audience_type' => 'all',
            'audience_value' => null,
            'starts_at' => null,
            'expires_at' => null,
            'created_by' => $admin->id,
        ]);
        echo "✓ Created announcement: '{$announcement1->title}' (All users, Info, Medium)\n";

        // 2. Urgent maintenance notice (all users, warning, urgent priority)
        $announcement2 = Announcement::create([
            'title' => 'Scheduled Maintenance Tonight',
            'message' => 'We will be performing scheduled maintenance tonight from 11 PM to 1 AM. The platform may be temporarily unavailable during this time. We apologize for any inconvenience.',
            'type' => 'warning',
            'priority' => 'urgent',
            'is_active' => true,
            'audience_type' => 'all',
            'audience_value' => null,
            'starts_at' => $now->copy()->subHours(2), // Started 2 hours ago
            'expires_at' => $now->copy()->addDays(1), // Expires in 1 day
            'created_by' => $admin->id,
        ]);
        echo "✓ Created announcement: '{$announcement2->title}' (All users, Warning, Urgent)\n";

        // 3. Success story (all users, success, low priority)
        $announcement3 = Announcement::create([
            'title' => 'New Feature: Video Calls!',
            'message' => 'We are thrilled to announce that video calls are now available for all active sessions! You can now have face-to-face conversations with your trading partners directly through the platform.',
            'type' => 'success',
            'priority' => 'low',
            'is_active' => true,
            'audience_type' => 'all',
            'audience_value' => null,
            'starts_at' => null,
            'expires_at' => null,
            'created_by' => $admin->id,
        ]);
        echo "✓ Created announcement: '{$announcement3->title}' (All users, Success, Low)\n";

        // 4. User-specific announcement (regular users only, info, high priority)
        $announcement4 = Announcement::create([
            'title' => 'Important: Profile Verification',
            'message' => 'Please make sure your profile is complete and verified to get the most out of SkillsXchange. Verified users get priority in trade matches and have access to premium features.',
            'type' => 'info',
            'priority' => 'high',
            'is_active' => true,
            'audience_type' => 'role',
            'audience_value' => json_encode(['user']),
            'starts_at' => null,
            'expires_at' => null,
            'created_by' => $admin->id,
        ]);
        echo "✓ Created announcement: '{$announcement4->title}' (Users only, Info, High)\n";

        // 5. Admin-only announcement (admin users only, danger, medium priority)
        $announcement5 = Announcement::create([
            'title' => 'Admin: System Update Required',
            'message' => 'All administrators are required to review the new moderation guidelines. Please check the admin dashboard for updated policies and procedures.',
            'type' => 'danger',
            'priority' => 'medium',
            'is_active' => true,
            'audience_type' => 'role',
            'audience_value' => json_encode(['admin']),
            'starts_at' => null,
            'expires_at' => null,
            'created_by' => $admin->id,
        ]);
        echo "✓ Created announcement: '{$announcement5->title}' (Admins only, Danger, Medium)\n";

        // 6. Future announcement (scheduled to start tomorrow)
        $announcement6 = Announcement::create([
            'title' => 'Upcoming Event: Skills Fair 2025',
            'message' => 'Join us for our annual Skills Fair on January 15th! Meet other traders, showcase your skills, and discover new learning opportunities. Registration opens next week.',
            'type' => 'info',
            'priority' => 'medium',
            'is_active' => true,
            'audience_type' => 'all',
            'audience_value' => null,
            'starts_at' => $now->copy()->addDay(), // Starts tomorrow
            'expires_at' => $now->copy()->addDays(30), // Expires in 30 days
            'created_by' => $admin->id,
        ]);
        echo "✓ Created announcement: '{$announcement6->title}' (Scheduled for tomorrow, All users)\n";

        // 7. Expired announcement (for testing expired state)
        $announcement7 = Announcement::create([
            'title' => 'This Announcement Has Expired',
            'message' => 'This is a test announcement that has already expired. It should not appear in the active announcements list.',
            'type' => 'info',
            'priority' => 'low',
            'is_active' => true,
            'audience_type' => 'all',
            'audience_value' => null,
            'starts_at' => $now->copy()->subDays(10), // Started 10 days ago
            'expires_at' => $now->copy()->subDay(), // Expired yesterday
            'created_by' => $admin->id,
        ]);
        echo "✓ Created announcement: '{$announcement7->title}' (Expired - for testing)\n";

        // 8. Inactive announcement (is_active = false)
        $announcement8 = Announcement::create([
            'title' => 'Draft Announcement (Inactive)',
            'message' => 'This announcement is inactive and should not appear to users. It can be activated later from the admin panel.',
            'type' => 'info',
            'priority' => 'low',
            'is_active' => false,
            'audience_type' => 'all',
            'audience_value' => null,
            'starts_at' => null,
            'expires_at' => null,
            'created_by' => $admin->id,
        ]);
        echo "✓ Created announcement: '{$announcement8->title}' (Inactive - for testing)\n";

        echo "\n" . str_repeat('=', 60) . "\n";
        echo "Announcement seeding completed!\n";
        echo str_repeat('=', 60) . "\n\n";
        echo "Summary:\n";
        echo "  - Active announcements for all users: 3\n";
        echo "  - Active announcements for regular users only: 1\n";
        echo "  - Active announcements for admins only: 1\n";
        echo "  - Scheduled (future) announcement: 1\n";
        echo "  - Expired announcement: 1 (for testing)\n";
        echo "  - Inactive announcement: 1 (for testing)\n\n";
        echo "Regular users should see 4 active announcements.\n";
        echo "Admin users should see 5 active announcements (including admin-only).\n";
    }
}

