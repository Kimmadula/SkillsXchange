<?php

namespace App\Console\Commands;

use App\Models\Trade;
use App\Models\TradeRequest;
use App\Models\User;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateTestSessions extends Command
{
    protected $signature = 'test:create-sessions';
    protected $description = 'Create test sessions: one starting soon and one ending soon';

    public function handle()
    {
        $this->info('Creating test sessions...');

        // Get or create test users
        $user1 = User::first();
        $user2 = User::skip(1)->first();

        if (!$user1 || !$user2) {
            $this->error('Need at least 2 users in the database. Please create users first.');
            return Command::FAILURE;
        }

        // Get skills
        $skill1 = Skill::first();
        $skill2 = Skill::skip(1)->first();

        if (!$skill1 || !$skill2) {
            $this->error('Need at least 2 skills in the database. Please create skills first.');
            return Command::FAILURE;
        }

        // Get user names for display
        $user1Name = $user1->username ?? $user1->firstname;
        $user2Name = $user2->username ?? $user2->firstname;

        $now = Carbon::now();

        // 1. Create session STARTING SOON (within 30 minutes)
        $this->info("\n=== Creating Session Starting Soon ===");
        $startTime = $now->copy()->addMinutes(30);
        $this->info("Session will start at: {$startTime->format('Y-m-d H:i:s')}");
        $this->info("Current time: {$now->format('Y-m-d H:i:s')}");

        try {
            $startingSoonTrade = Trade::create([
                'user_id' => $user1->id,
                'offering_skill_id' => $skill1->skill_id,
                'looking_skill_id' => $skill2->skill_id,
                'start_date' => $startTime->toDateString(),
                'end_date' => $startTime->copy()->addDays(7)->toDateString(),
                'available_from' => $startTime->toTimeString(),
                'available_to' => $startTime->copy()->addHours(2)->toTimeString(),
                'preferred_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'gender_pref' => 'any',
                'location' => 'Test Location',
                'session_type' => 'online',
                'use_username' => false,
                'status' => 'ongoing',
            ]);

            $this->info("✓ Created Trade ID: {$startingSoonTrade->id}");
        } catch (\Exception $e) {
            $this->error("✗ Failed to create starting soon trade: {$e->getMessage()}");
            $this->error("Error details: " . $e->getTraceAsString());
            return Command::FAILURE;
        }

        // Create accepted trade request
        try {
            $startingRequest = TradeRequest::create([
                'trade_id' => $startingSoonTrade->id,
                'requester_id' => $user2->id,
                'status' => 'accepted',
                'responded_at' => now(),
                'message' => 'Test request for session starting soon',
            ]);

            $this->info("✓ Created accepted TradeRequest ID: {$startingRequest->id}");
        } catch (\Exception $e) {
            $this->error("✗ Failed to create trade request: {$e->getMessage()}");
            // Continue anyway - trade is created
        }
        $this->info("  - Trade Owner: User ID {$user1->id} ({$user1Name})");
        $this->info("  - Requester: User ID {$user2->id} ({$user2Name})");
        $this->info("  - Reminders will be sent at:");
        $this->info("    • {$startTime->copy()->subMinutes(60)->format('H:i:s')} (60 min before)");
        $this->info("    • {$startTime->copy()->subMinutes(30)->format('H:i:s')} (30 min before)");
        $this->info("    • {$startTime->copy()->subMinutes(15)->format('H:i:s')} (15 min before)");

        // 2. Create session ENDING SOON (expires in 1 hour)
        $this->info("\n=== Creating Session Ending Soon ===");
        $endTime = $now->copy()->addHour();
        $this->info("Session will end at: {$endTime->format('Y-m-d H:i:s')}");

        try {
            $endingSoonTrade = Trade::create([
                'user_id' => $user2->id,
                'offering_skill_id' => $skill2->skill_id,
                'looking_skill_id' => $skill1->skill_id,
                'start_date' => $now->copy()->subDays(5)->toDateString(),
                'end_date' => $endTime->toDateString(),
                'available_from' => '09:00:00',
                'available_to' => '17:00:00',
                'preferred_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'gender_pref' => 'any',
                'location' => 'Test Location',
                'session_type' => 'online',
                'use_username' => false,
                'status' => 'ongoing',
            ]);

            $this->info("✓ Created Trade ID: {$endingSoonTrade->id}");
        } catch (\Exception $e) {
            $this->error("✗ Failed to create ending soon trade: {$e->getMessage()}");
            $this->error("Error details: " . $e->getTraceAsString());
            return Command::FAILURE;
        }

        // Create accepted trade request
        try {
            $endingRequest = TradeRequest::create([
                'trade_id' => $endingSoonTrade->id,
                'requester_id' => $user1->id,
                'status' => 'accepted',
                'responded_at' => now(),
                'message' => 'Test request for session ending soon',
            ]);

            $this->info("✓ Created accepted TradeRequest ID: {$endingRequest->id}");
        } catch (\Exception $e) {
            $this->error("✗ Failed to create trade request: {$e->getMessage()}");
            // Continue anyway - trade is created
        }
        $this->info("  - Trade Owner: User ID {$user2->id} ({$user2Name})");
        $this->info("  - Requester: User ID {$user1->id} ({$user1Name})");
        $this->info("  - Session expires in 1 hour");

        // Verify trades were created
        $this->info("\n" . str_repeat('=', 60));
        $this->info("Verifying created trades...");
        
        $verifyStarting = Trade::find($startingSoonTrade->id);
        $verifyEnding = Trade::find($endingSoonTrade->id);
        
        if ($verifyStarting) {
            $this->info("✓ Starting soon trade verified: ID {$verifyStarting->id}, Status: {$verifyStarting->status}");
        } else {
            $this->error("✗ Starting soon trade not found in database!");
        }
        
        if ($verifyEnding) {
            $this->info("✓ Ending soon trade verified: ID {$verifyEnding->id}, Status: {$verifyEnding->status}");
        } else {
            $this->error("✗ Ending soon trade not found in database!");
        }
        
        $this->info(str_repeat('=', 60));
        $this->info("✅ Test sessions created successfully!");
        $this->info(str_repeat('=', 60));
        $this->info("\nNext steps:");
        $this->info("1. Run: php artisan notifications:send-pre-session-reminders");
        $this->info("   (This should find the 'starting soon' session)");
        $this->info("2. Check notifications in the UI");
        $this->info("3. Check the 'Ongoing Trades' page to see the new sessions");

        return Command::SUCCESS;
    }
}

