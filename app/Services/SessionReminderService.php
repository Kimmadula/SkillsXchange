<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\TradeRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SessionReminderService
{
    /**
     * Check all active sessions and create reminders for sessions
     * starting or expiring in 60 minutes
     */
    public function checkAndCreateReminders()
    {
        $now = Carbon::now();
        $sixtyMinutesFromNow = $now->copy()->addMinutes(60);
        
        // Get all active/ongoing trades with relationships loaded
        $activeTrades = Trade::with(['user', 'offeringSkill', 'lookingSkill'])
            ->where('status', 'ongoing')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();
        
        foreach ($activeTrades as $trade) {
            // Get both users involved in the trade
            $users = $this->getTradeUsers($trade);
            
            if (empty($users)) {
                continue;
            }
            
            // Check if session is starting in 60 minutes
            $this->checkSessionStarting($trade, $users, $now, $sixtyMinutesFromNow);
            
            // Check if session is expiring in 60 minutes
            $this->checkSessionExpiring($trade, $users, $now, $sixtyMinutesFromNow);
        }
    }
    
    /**
     * Get both users involved in a trade
     */
    private function getTradeUsers(Trade $trade)
    {
        $users = [];
        
        // Add the trade creator
        if ($trade->user) {
            $users[] = $trade->user;
        }
        
        // Find accepted request to get the requester
        $acceptedRequest = TradeRequest::with('requester')
            ->where('trade_id', $trade->id)
            ->where('status', 'accepted')
            ->first();
        
        if ($acceptedRequest && $acceptedRequest->requester) {
            $users[] = $acceptedRequest->requester;
        }
        
        return array_unique($users, SORT_REGULAR);
    }
    
    /**
     * Check if session is starting in 60 minutes and create reminders
     */
    private function checkSessionStarting(Trade $trade, array $users, Carbon $now, Carbon $sixtyMinutesFromNow)
    {
        if (!$trade->start_date || !$trade->available_from) {
            return;
        }
        
        // Combine start_date and available_from to get full datetime
        $sessionStart = Carbon::parse($trade->start_date . ' ' . $trade->available_from);
        
        // Check if session starts between now and 60 minutes from now
        // Allow a 5-minute window to account for timing differences
        if ($sessionStart->between($now->copy()->subMinutes(5), $sixtyMinutesFromNow->copy()->addMinutes(5))) {
            foreach ($users as $user) {
                // Check if notification already exists to avoid duplicates
                // Check for notifications created in the last 10 minutes for this trade
                $existingNotification = DB::table('user_notifications')
                    ->where('user_id', $user->id)
                    ->where('type', 'pre_session_reminder')
                    ->where('created_at', '>=', $now->copy()->subMinutes(10))
                    ->get()
                    ->filter(function($notification) use ($trade) {
                        $data = json_decode($notification->data, true);
                        return isset($data['trade_id']) && $data['trade_id'] == $trade->id 
                            && isset($data['minutes_before']) && $data['minutes_before'] == 60;
                    })
                    ->first();
                
                if (!$existingNotification) {
                    DB::table('user_notifications')->insert([
                        'user_id' => $user->id,
                        'type' => 'pre_session_reminder',
                        'data' => json_encode([
                            'trade_id' => $trade->id,
                            'minutes_before' => 60,
                            'message' => 'Session starting in 60 minutes',
                            'start_date' => $trade->start_date,
                            'available_from' => $trade->available_from,
                            'offering_skill' => optional($trade->offeringSkill)->name ?? 'N/A',
                            'looking_skill' => optional($trade->lookingSkill)->name ?? 'N/A',
                        ]),
                        'read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
    
    /**
     * Check if session is expiring in 60 minutes and create reminders
     */
    private function checkSessionExpiring(Trade $trade, array $users, Carbon $now, Carbon $sixtyMinutesFromNow)
    {
        if (!$trade->end_date || !$trade->available_to) {
            return;
        }
        
        // Combine end_date and available_to to get full datetime
        $sessionEnd = Carbon::parse($trade->end_date . ' ' . $trade->available_to);
        
        // Check if session expires between now and 60 minutes from now
        // Allow a 5-minute window to account for timing differences
        if ($sessionEnd->between($now->copy()->subMinutes(5), $sixtyMinutesFromNow->copy()->addMinutes(5))) {
            foreach ($users as $user) {
                // Check if notification already exists to avoid duplicates
                // Check for notifications created in the last 10 minutes for this trade
                $existingNotification = DB::table('user_notifications')
                    ->where('user_id', $user->id)
                    ->where('type', 'session_expiration_warning')
                    ->where('created_at', '>=', $now->copy()->subMinutes(10))
                    ->get()
                    ->filter(function($notification) use ($trade) {
                        $data = json_decode($notification->data, true);
                        return isset($data['trade_id']) && $data['trade_id'] == $trade->id 
                            && isset($data['hours_before']) && $data['hours_before'] == 1;
                    })
                    ->first();
                
                if (!$existingNotification) {
                    DB::table('user_notifications')->insert([
                        'user_id' => $user->id,
                        'type' => 'session_expiration_warning',
                        'data' => json_encode([
                            'trade_id' => $trade->id,
                            'hours_before' => 1,
                            'message' => 'Session expiring in 60 minutes',
                            'end_date' => $trade->end_date,
                            'available_to' => $trade->available_to,
                            'offering_skill' => optional($trade->offeringSkill)->name ?? 'N/A',
                            'looking_skill' => optional($trade->lookingSkill)->name ?? 'N/A',
                        ]),
                        'read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}

