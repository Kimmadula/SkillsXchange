<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update a test user for database connection testing
        $user = User::updateOrCreate(
            ['username' => 'testuser'], // Find by username
            [
                'firstname' => 'Test',
                'lastname' => 'User',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'bdate' => '1990-01-01',
                'address' => 'Test Address, Test City',
                'gender' => 'other',
                'email_verified_at' => now(),
                'is_verified' => true,
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->command->info('Test user created successfully!');
        } else {
            $this->command->info('Test user updated successfully!');
        }
        
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password123');
    }
}
