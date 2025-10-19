<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Skill;
use App\Models\SkillAcquisitionHistory;
use App\Models\UserSkill;

class SkillAcquisitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get some users and skills for testing
        $users = User::take(3)->get();
        $skills = Skill::take(5)->get();

        if ($users->isEmpty() || $skills->isEmpty()) {
            $this->command->info('No users or skills found. Please run UserSeeder and SkillSeeder first.');
            return;
        }

        foreach ($users as $user) {
            // Add 2-3 random skills to each user
            $randomSkills = $skills->random(rand(2, 3));
            
            foreach ($randomSkills as $skill) {
                // Check if user already has this skill
                $existingSkill = UserSkill::where('user_id', $user->id)
                    ->where('skill_id', $skill->skill_id)
                    ->first();

                if (!$existingSkill) {
                    // Add skill to user_skills table
                    UserSkill::create([
                        'user_id' => $user->id,
                        'skill_id' => $skill->skill_id
                    ]);

                    // Add skill acquisition history
                    SkillAcquisitionHistory::create([
                        'user_id' => $user->id,
                        'skill_id' => $skill->skill_id,
                        'trade_id' => null, // Sample data
                        'acquisition_method' => 'manual_add',
                        'score_achieved' => 100,
                        'notes' => 'Sample skill acquisition for testing',
                        'acquired_at' => now()->subDays(rand(1, 30))
                    ]);
                }
            }
        }

        $this->command->info('Skill acquisitions created successfully!');
    }
}