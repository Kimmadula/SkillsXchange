<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DwightUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users 4 and 5 (dwight and dwight123) needed for TradeSeeder
        $users = [
            [
                'id' => 4,
                'firstname' => 'dwight',
                'middlename' => null,
                'lastname' => 'schrute',
                'username' => 'dwight',
                'email' => 'dwight@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'skill_id' => 1, // Web Development
                'is_verified' => 1,
                'role' => 'user',
                'plan' => 'free',
                'token_balance' => 0
            ],
            [
                'id' => 5,
                'firstname' => 'dwight',
                'middlename' => null,
                'lastname' => 'schrute',
                'username' => 'dwight123',
                'email' => 'dwight123@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'skill_id' => 1, // Web Development
                'is_verified' => 1,
                'role' => 'user',
                'plan' => 'free',
                'token_balance' => 0
            ]
        ];

        foreach ($users as $userData) {
            // Check if user already exists
            $existingUser = User::where('id', $userData['id'])->first();
            if (!$existingUser) {
                User::create($userData);
                echo "Created user: {$userData['username']} with skill_id: {$userData['skill_id']}\n";
            } else {
                echo "User {$userData['username']} already exists, updating skill_id and email_verified_at\n";
                $existingUser->update([
                    'skill_id' => $userData['skill_id'],
                    'email_verified_at' => $userData['email_verified_at']
                ]);
            }
        }
    }
}

