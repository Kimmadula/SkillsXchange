<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdditionalUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create additional users with different skills
        $users = [
            [
                'id' => 6,
                'firstname' => 'maria',
                'middlename' => 'cruz',
                'lastname' => 'santos',
                'username' => 'maria_cook',
                'email' => 'maria@example.com',
                'password' => Hash::make('password'),
                'skill_id' => 3, // Cooking
                'is_verified' => 1,
                'role' => 'user',
                'plan' => 'free',
                'token_balance' => 0
            ],
            [
                'id' => 7,
                'firstname' => 'juan',
                'middlename' => 'dela',
                'lastname' => 'cruz',
                'username' => 'juan_knife',
                'email' => 'juan@example.com',
                'password' => Hash::make('password'),
                'skill_id' => 4, // Knife skills
                'is_verified' => 1,
                'role' => 'user',
                'plan' => 'free',
                'token_balance' => 0
            ],
            [
                'id' => 8,
                'firstname' => 'ana',
                'middlename' => 'garcia',
                'lastname' => 'lopez',
                'username' => 'ana_design',
                'email' => 'ana@example.com',
                'password' => Hash::make('password'),
                'skill_id' => 2, // Graphic Design (skill_id 2)
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
                echo "User {$userData['username']} already exists, updating skill_id to {$userData['skill_id']}\n";
                $existingUser->update(['skill_id' => $userData['skill_id']]);
            }
        }
    }
}
