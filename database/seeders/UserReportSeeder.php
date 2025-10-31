<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserReport;

class UserReportSeeder extends Seeder
{
    /**
     * Seed a few sample user reports for testing the admin moderation UI.
     */
    public function run(): void
    {
        // Ensure we have at least two users to relate reports to
        $admin = User::where('email', 'admin@example.com')->first();
        if (!$admin) {
            $admin = User::create([
                'firstname' => 'System',
                'lastname' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }

        $reporter = User::where('email', 'test@example.com')->first();
        if (!$reporter) {
            $reporter = User::create([
                'firstname' => 'Test',
                'lastname' => 'User',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Optional: pick any other regular user as the reported user; fallback to admin
        $anotherUser = User::where('role', '!=', 'admin')->where('id', '!=', $reporter->id)->first() ?: $admin;

        // Create a small set of reports with varying reasons/statuses
        $payloads = [
            [
                'reporter_id' => $reporter->id,
                'reported_user_id' => $anotherUser->id,
                'trade_id' => null,
                'context' => 'chat',
                'reason' => 'harassment',
                'description' => 'User used offensive language during a chat session.',
                'evidence' => [
                    'messages' => ['Offensive message excerpt 1', 'Offensive message excerpt 2']
                ],
                'status' => 'pending',
            ],
            [
                'reporter_id' => $reporter->id,
                'reported_user_id' => $anotherUser->id,
                'trade_id' => null,
                'context' => 'trade',
                'reason' => 'fraud',
                'description' => 'Counterparty requested payment outside the platform.',
                'evidence' => [
                    'notes' => 'Screenshot links can be attached here later.'
                ],
                'status' => 'under_review',
            ],
            [
                'reporter_id' => $reporter->id,
                'reported_user_id' => $anotherUser->id,
                'trade_id' => null,
                'context' => 'profile',
                'reason' => 'spam',
                'description' => 'Repeated unsolicited messages promoting external services.',
                'evidence' => null,
                'status' => 'resolved',
                'admin_notes' => 'User warned; behavior ceased.',
            ],
        ];

        foreach ($payloads as $data) {
            UserReport::create($data);
        }
    }
}


