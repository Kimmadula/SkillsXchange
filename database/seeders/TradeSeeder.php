<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Trade;
use App\Models\User;
use App\Models\Skill;

class TradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing trades and related data
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Trade::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create trades with proper skill matching
        $trades = [
            [
                'user_id' => 4, // dwight (skill_id: 1 - Web Development)
                'offering_skill_id' => 1, // Web Development (matches registered skill)
                'looking_skill_id' => 3, // Cooking (what they want to learn)
                'start_date' => '2025-12-12',
                'end_date' => '2026-01-01',
                'available_from' => '18:31:00',
                'available_to' => '20:31:00',
                'preferred_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'gender_pref' => 'any',
                'location' => 'inayawan',
                'session_type' => 'any',
                'use_username' => 1,
                'status' => 'open'
            ],
            [
                'user_id' => 5, // dwight123 (skill_id: 1 - Web Development)
                'offering_skill_id' => 1, // Web Development (matches registered skill)
                'looking_skill_id' => 4, // Knife skills (what they want to learn)
                'start_date' => '2025-12-12',
                'end_date' => '2026-01-01',
                'available_from' => '18:31:00',
                'available_to' => '20:31:00',
                'preferred_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'gender_pref' => 'any',
                'location' => null,
                'session_type' => 'any',
                'use_username' => 0,
                'status' => 'open'
            ],
            [
                'user_id' => 6, // maria_cook (skill_id: 3 - Cooking)
                'offering_skill_id' => 3, // Cooking (matches registered skill)
                'looking_skill_id' => 1, // Web Development (what they want to learn)
                'start_date' => '2025-12-12',
                'end_date' => '2026-01-01',
                'available_from' => '18:31:00',
                'available_to' => '20:31:00',
                'preferred_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'gender_pref' => 'any',
                'location' => null,
                'session_type' => 'any',
                'use_username' => 0,
                'status' => 'open'
            ],
            [
                'user_id' => 7, // juan_knife (skill_id: 4 - Knife skills)
                'offering_skill_id' => 4, // Knife skills (matches registered skill)
                'looking_skill_id' => 1, // Web Development (what they want to learn)
                'start_date' => '2025-08-30',
                'end_date' => '2025-08-31',
                'available_from' => '13:30:00',
                'available_to' => '15:00:00',
                'preferred_days' => ['Mon', 'Thu', 'Fri'],
                'gender_pref' => 'any',
                'location' => null,
                'session_type' => 'any',
                'use_username' => 1,
                'status' => 'open'
            ],
            [
                'user_id' => 8, // ana_design (skill_id: 2 - Graphic Design)
                'offering_skill_id' => 2, // Graphic Design (matches registered skill)
                'looking_skill_id' => 1, // Web Development (what they want to learn)
                'start_date' => '2025-08-29',
                'end_date' => '2025-08-31',
                'available_from' => '13:00:00',
                'available_to' => '15:00:00',
                'preferred_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'gender_pref' => 'any',
                'location' => null,
                'session_type' => 'any',
                'use_username' => 0,
                'status' => 'open'
            ]
        ];

        foreach ($trades as $tradeData) {
            Trade::create($tradeData);
        }

        echo "Created " . count($trades) . " trades with proper skill matching!\n";
    }
}
