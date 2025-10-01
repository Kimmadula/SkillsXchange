<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@example.com'], // unique key
            [
                'firstname'       => 'System',
                'middlename'      => null,
                'lastname'        => 'Admin',
                'username'        => 'admin',
                'email'           => 'admin@example.com',
                'email_verified_at' => now(),
                'password'        => Hash::make('admin123'), // change later
                'photo'           => null,
                'is_verified'     => true,
                'role'            => 'admin',
                'plan'            => 'premium',
                'token_balance'   => 100,
                'date_created'    => now(),
                'skill_id'        => null,
                'remember_token'  => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]
        );
    }
}
