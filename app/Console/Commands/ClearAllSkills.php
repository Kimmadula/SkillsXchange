<?php

namespace App\Console\Commands;

use App\Models\Skill;
use Illuminate\Console\Command;

class ClearAllSkills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skills:clear-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all skills from the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Clearing all skills from the database...');

        try {
            $skillCount = Skill::count();
            
            if ($skillCount === 0) {
                $this->info('No skills found in the database.');
                return 0;
            }

            $this->info("Found {$skillCount} skills to delete.");

            if ($this->confirm('Are you sure you want to delete all skills?')) {
                Skill::truncate();
                $this->info("Successfully deleted all {$skillCount} skills.");
            } else {
                $this->info('Operation cancelled.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error clearing skills: " . $e->getMessage());
            return 1;
        }
    }
}
