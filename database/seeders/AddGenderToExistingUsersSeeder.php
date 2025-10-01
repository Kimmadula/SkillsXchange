<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class AddGenderToExistingUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing users with gender based on their names/usernames
        $users = [
            [
                'id' => 4,
                'gender' => 'male', // dwight - male name
            ],
            [
                'id' => 5,
                'gender' => 'male', // dwight123 - male name
            ],
            [
                'id' => 6,
                'gender' => 'female', // maria - female name
            ],
            [
                'id' => 7,
                'gender' => 'male', // juan - male name
            ],
            [
                'id' => 8,
                'gender' => 'female', // ana - female name
            ]
        ];

        foreach ($users as $userData) {
            $user = User::find($userData['id']);
            if ($user) {
                $user->update(['gender' => $userData['gender']]);
                echo "Updated user {$user->username} with gender: {$userData['gender']}\n";
            }
        }

        echo "Gender data added to existing users!\n";
    }
}
