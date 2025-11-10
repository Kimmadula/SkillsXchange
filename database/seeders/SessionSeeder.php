<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Trade;
use App\Models\TradeRequest;
use App\Models\User;

class SessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates accepted trade requests to enable chat sessions.
     */
    public function run(): void
    {
        // Find dwight's trade (user_id = 4, offering Web Development, looking for Cooking)
        $dwightTrade = Trade::where('user_id', 4)->first();
        
        if (!$dwightTrade) {
            echo "Dwight's trade not found. Please run TradeSeeder first.\n";
            return;
        }

        // Find maria_cook (user_id = 6, has Cooking skill)
        $maria = User::where('username', 'maria_cook')->first();
        
        if (!$maria) {
            echo "Maria (maria_cook) not found. Please run AdditionalUserSeeder first.\n";
            return;
        }

        // Check if a request already exists
        $existingRequest = TradeRequest::where('trade_id', $dwightTrade->id)
            ->where('requester_id', $maria->id)
            ->first();

        if ($existingRequest) {
            // Update existing request to accepted
            $existingRequest->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);
            echo "Updated existing trade request to accepted status.\n";
        } else {
            // Create new accepted trade request
            TradeRequest::create([
                'trade_id' => $dwightTrade->id,
                'requester_id' => $maria->id,
                'status' => 'accepted',
                'responded_at' => now(),
                'message' => 'I would like to learn Web Development from you!',
            ]);
            echo "Created accepted trade request from maria_cook to dwight's trade.\n";
        }

        // Update trade status to 'ongoing' to enable the session
        $dwightTrade->update(['status' => 'ongoing']);
        
        echo "Session created! Dwight (user 4) and maria_cook (user 6) now have an active chat session.\n";
        echo "Trade ID: {$dwightTrade->id}\n";
        echo "You can access the session at: /chat/{$dwightTrade->id}\n";
    }
}

