<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Trade;
use App\Models\TradeRequest;
use App\Models\User;
use App\Models\Skill;
use Carbon\Carbon;

class TestSessionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test sessions: one starting soon and one ending soon.
     */
    public function run(): void
    {
        echo "Creating test sessions for reminder testing...\n\n";

        // Get dwight user (user_id = 4)
        $dwight = User::where('username', 'dwight')->orWhere('id', 4)->first();
        
        if (!$dwight) {
            echo "ERROR: Dwight user not found. Please run DwightUsersSeeder first.\n";
            return;
        }

        // Get partner user (prefer maria_cook, otherwise get any other user)
        $partner = User::where('username', 'maria_cook')->orWhere('id', 6)->first();
        
        if (!$partner) {
            // Fallback to any other user that's not dwight
            $partner = User::where('id', '!=', $dwight->id)->first();
        }

        if (!$partner) {
            echo "ERROR: Need at least 2 users in the database. Please create users first.\n";
            return;
        }

        // Get user names for display
        $dwightName = $dwight->username ?? ($dwight->firstname . ' ' . $dwight->lastname);
        $partnerName = $partner->username ?? ($partner->firstname . ' ' . $partner->lastname);
        
        echo "Using users:\n";
        echo "  - Dwight: ID {$dwight->id} ({$dwightName})\n";
        echo "  - Partner: ID {$partner->id} ({$partnerName})\n\n";

        // Get skills
        $skill1 = Skill::first();
        $skill2 = Skill::skip(1)->first();

        if (!$skill1 || !$skill2) {
            echo "ERROR: Need at least 2 skills in the database. Please create skills first.\n";
            return;
        }

        echo "Using skills:\n";
        echo "  - Skill 1: {$skill1->name} (ID: {$skill1->skill_id})\n";
        echo "  - Skill 2: {$skill2->name} (ID: {$skill2->skill_id})\n\n";

        $now = Carbon::now();

        // 1. Create session STARTING SOON (within 30 minutes)
        echo "=== Creating Session Starting Soon ===\n";
        $startTime = $now->copy()->addMinutes(30);
        echo "Session will start at: {$startTime->format('Y-m-d H:i:s')}\n";
        echo "Current time: {$now->format('Y-m-d H:i:s')}\n";

        try {
            $startingSoonTrade = Trade::create([
                'user_id' => $dwight->id,
                'offering_skill_id' => $skill1->skill_id,
                'looking_skill_id' => $skill2->skill_id,
                'start_date' => $startTime->toDateString(),
                'end_date' => $startTime->copy()->addDays(7)->toDateString(),
                'available_from' => $startTime->toTimeString(),
                'available_to' => $startTime->copy()->addHours(2)->toTimeString(),
                'preferred_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'gender_pref' => 'any',
                'location' => 'Test Location - Starting Soon',
                'session_type' => 'online',
                'use_username' => false,
                'status' => 'ongoing',
            ]);

            echo "✓ Created Trade ID: {$startingSoonTrade->id}\n";

            // Create accepted trade request
            $startingRequest = TradeRequest::create([
                'trade_id' => $startingSoonTrade->id,
                'requester_id' => $partner->id,
                'status' => 'accepted',
                'responded_at' => now(),
                'message' => 'Test request for session starting soon',
            ]);

            echo "✓ Created accepted TradeRequest ID: {$startingRequest->id}\n";
            echo "  - Trade Owner: {$dwightName} (ID {$dwight->id})\n";
            echo "  - Requester: {$partnerName} (ID {$partner->id})\n";
            echo "  - Reminders will be sent at:\n";
            echo "    • {$startTime->copy()->subMinutes(60)->format('H:i:s')} (60 min before) - WON'T be sent (session starts in 30 min)\n";
            echo "    • {$startTime->copy()->subMinutes(30)->format('H:i:s')} (30 min before) - WILL be sent NOW\n";
            echo "    • {$startTime->copy()->subMinutes(15)->format('H:i:s')} (15 min before) - WILL be sent in 15 minutes\n\n";

        } catch (\Exception $e) {
            echo "✗ ERROR: Failed to create starting soon trade: {$e->getMessage()}\n";
            echo "Stack trace: {$e->getTraceAsString()}\n\n";
        }

        // 2. Create session ENDING SOON (expires in 1 hour)
        echo "=== Creating Session Ending Soon ===\n";
        $endTime = $now->copy()->addHour();
        echo "Session will end at: {$endTime->format('Y-m-d H:i:s')}\n";

        try {
            $endingSoonTrade = Trade::create([
                'user_id' => $partner->id,
                'offering_skill_id' => $skill2->skill_id,
                'looking_skill_id' => $skill1->skill_id,
                'start_date' => $now->copy()->subDays(5)->toDateString(),
                'end_date' => $endTime->toDateString(),
                'available_from' => '09:00:00',
                'available_to' => '17:00:00',
                'preferred_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'gender_pref' => 'any',
                'location' => 'Test Location - Ending Soon',
                'session_type' => 'online',
                'use_username' => false,
                'status' => 'ongoing',
            ]);

            echo "✓ Created Trade ID: {$endingSoonTrade->id}\n";

            // Create accepted trade request
            $endingRequest = TradeRequest::create([
                'trade_id' => $endingSoonTrade->id,
                'requester_id' => $dwight->id,
                'status' => 'accepted',
                'responded_at' => now(),
                'message' => 'Test request for session ending soon',
            ]);

            echo "✓ Created accepted TradeRequest ID: {$endingRequest->id}\n";
            echo "  - Trade Owner: {$partnerName} (ID {$partner->id})\n";
            echo "  - Requester: {$dwightName} (ID {$dwight->id})\n";
            echo "  - Session expires in 1 hour\n\n";

        } catch (\Exception $e) {
            echo "✗ ERROR: Failed to create ending soon trade: {$e->getMessage()}\n";
            echo "Stack trace: {$e->getTraceAsString()}\n\n";
        }

        // Verify trades were created
        echo str_repeat('=', 60) . "\n";
        echo "Verification:\n";
        
        $startingId = isset($startingSoonTrade) ? $startingSoonTrade->id : 0;
        $endingId = isset($endingSoonTrade) ? $endingSoonTrade->id : 0;
        $verifyStarting = $startingId > 0 ? Trade::find($startingId) : null;
        $verifyEnding = $endingId > 0 ? Trade::find($endingId) : null;
        
        if ($verifyStarting) {
            echo "✓ Starting soon trade verified: ID {$verifyStarting->id}, Status: {$verifyStarting->status}\n";
        } else {
            echo "✗ Starting soon trade NOT found in database!\n";
        }
        
        if ($verifyEnding) {
            echo "✓ Ending soon trade verified: ID {$verifyEnding->id}, Status: {$verifyEnding->status}\n";
        } else {
            echo "✗ Ending soon trade NOT found in database!\n";
        }
        
        echo str_repeat('=', 60) . "\n";
        echo "✅ Test sessions seeder completed!\n\n";
        echo "Next steps:\n";
        echo "1. Run: php artisan notifications:send-pre-session-reminders\n";
        echo "   (This should find the 'starting soon' session and send 30-min reminder)\n";
        echo "2. Check notifications in the UI (bell icon)\n";
        echo "3. Check 'Ongoing Trades' page to see the new sessions\n";
    }
}

