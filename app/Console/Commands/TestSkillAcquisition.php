<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Skill;
use App\Models\SkillAcquisitionHistory;
use App\Models\UserSkill;

class TestSkillAcquisition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:skill-acquisition {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test skill acquisition functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
        } else {
            $user = User::first();
            if (!$user) {
                $this->error("No users found in database.");
                return 1;
            }
        }

        $this->info("Testing skill acquisition for user: {$user->name} (ID: {$user->id})");

        // Get some skills
        $skills = Skill::take(3)->get();
        if ($skills->isEmpty()) {
            $this->error("No skills found. Please run SkillSeeder first.");
            return 1;
        }

        $this->info("Available skills: " . $skills->pluck('name')->join(', '));

        // Check current acquired skills
        $currentSkills = $user->getAcquiredSkills();
        $this->info("Current acquired skills count: " . $currentSkills->count());
        $this->info("Current acquired skills: " . $currentSkills->pluck('name')->join(', '));

        // Add some test skills
        $addedSkills = [];
        foreach ($skills as $skill) {
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
                    'trade_id' => null,
                    'acquisition_method' => 'manual_add',
                    'score_achieved' => 100,
                    'notes' => 'Test skill acquisition via command',
                    'acquired_at' => now()
                ]);

                $addedSkills[] = $skill->name;
                $this->info("Added skill: {$skill->name}");
            } else {
                $this->warn("User already has skill: {$skill->name}");
            }
        }

        // Check acquired skills again
        $newSkills = $user->getAcquiredSkills();
        $this->info("New acquired skills count: " . $newSkills->count());
        $this->info("New acquired skills: " . $newSkills->pluck('name')->join(', '));

        // Test the profile controller logic
        $this->info("\nTesting ProfileController logic:");
        $acquiredSkills = $user->getAcquiredSkills();
        $this->info("Acquired skills from getAcquiredSkills(): " . $acquiredSkills->count());
        
        if ($acquiredSkills->count() > 0) {
            $this->info("Skills that should appear in profile:");
            foreach ($acquiredSkills as $skill) {
                $this->info("- {$skill->name} (ID: {$skill->skill_id})");
            }
        } else {
            $this->warn("No skills found - this might be why the profile shows no skills");
        }

        $this->info("\nTest completed successfully!");
        return 0;
    }
}