<?php

namespace App\Console\Commands;

use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendPreSessionReminders extends Command
{
    protected $signature = 'notifications:send-pre-session-reminders';
    protected $description = 'Send reminders before sessions start (e.g., 60, 30, 15 minutes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $this->info('Scanning for upcoming sessions at ' . $now->toDateTimeString());
        $this->info('Current time: ' . $now->format('Y-m-d H:i:s'));

        // Windows: interpret times from Trade (start_date + available_from)
        $windows = [60, 30, 15]; // minutes

        // Debug: Show all ongoing trades
        $allOngoingTrades = Trade::where('status', 'ongoing')->get();
        $this->info("Found {$allOngoingTrades->count()} ongoing trade(s)");
        
        if ($allOngoingTrades->count() > 0) {
            $this->info("Ongoing trades details:");
            foreach ($allOngoingTrades as $trade) {
                $this->info("  - Trade ID: {$trade->id}, Start Date: {$trade->start_date}, Available From: {$trade->available_from}, Status: {$trade->status}");
            }
        }

        $totalRemindersSent = 0;

        foreach ($windows as $minutesBefore) {
            $targetTime = $now->copy()->addMinutes($minutesBefore);
            $this->info("\n--- Checking for {$minutesBefore}-minute reminders ---");
            $this->info("Target time: {$targetTime->format('Y-m-d H:i:s')}");
            $this->info("Looking for sessions starting on: {$targetTime->toDateString()} at time: {$targetTime->toTimeString()}");

            // Find trades starting at target time window (±5 minutes tolerance)
            $timeWindowStart = $targetTime->copy()->subMinutes(5)->toTimeString();
            $timeWindowEnd = $targetTime->copy()->addMinutes(5)->toTimeString();
            $this->info("Time window: {$timeWindowStart} to {$timeWindowEnd}");

            $query = Trade::where('status', 'ongoing')
                ->whereDate('start_date', $targetTime->toDateString())
                ->where('available_from', '>=', $timeWindowStart)
                ->where('available_from', '<=', $timeWindowEnd);

            $trades = $query->get();
            $this->info("Found {$trades->count()} trade(s) matching criteria");

            foreach ($trades as $trade) {
                $this->info("  Processing Trade ID: {$trade->id}");
                
                // Notify trade owner and accepted requester if present
                $recipients = collect();
                if ($trade->user) {
                    $recipients->push($trade->user);
                    $this->info("    - Trade owner: User ID {$trade->user->id}");
                }
                $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
                if ($acceptedRequest && $acceptedRequest->requester) {
                    $recipients->push($acceptedRequest->requester);
                    $this->info("    - Accepted requester: User ID {$acceptedRequest->requester->id}");
                }

                $this->info("    - Total recipients: {$recipients->count()}");

                foreach ($recipients as $user) {
                    $cacheKey = "reminder:pre_session:trade:{$trade->id}:user:{$user->id}:min:{$minutesBefore}";
                    if (Cache::add($cacheKey, true, now()->addHours(6))) { // prevent duplicates for 6h
                        try {
                            // Check if notification already exists
                            $exists = DB::table('user_notifications')
                                ->where('user_id', $user->id)
                                ->where('type', 'pre_session_reminder')
                                ->where('data', 'like', '%"trade_id":' . $trade->id . '%')
                                ->where('data', 'like', '%"minutes_before":' . $minutesBefore . '%')
                                ->exists();

                            if (!$exists) {
                                DB::table('user_notifications')->insert([
                                    'user_id' => $user->id,
                                    'type' => 'pre_session_reminder',
                                    'data' => json_encode([
                                        'trade_id' => $trade->id,
                                        'minutes_before' => $minutesBefore,
                                        'message' => "Your session starts in {$minutesBefore} minutes.",
                                        'start_date' => $trade->start_date,
                                        'available_from' => $trade->available_from,
                                        'offering_skill' => optional($trade->offeringSkill)->name,
                                        'looking_skill' => optional($trade->lookingSkill)->name,
                                    ]),
                                    'read' => false,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $this->info("    ✓ Reminder sent to User ID {$user->id}");
                                $totalRemindersSent++;
                            } else {
                                $this->info("    - Reminder already exists for User ID {$user->id}, skipping");
                            }
                        } catch (\Throwable $e) {
                            $this->error("    ✗ Failed to send reminder to User ID {$user->id}: {$e->getMessage()}");
                            Log::warning('Pre-session reminder notify failed', [
                                'trade_id' => $trade->id,
                                'user_id' => $user->id,
                                'minutes_before' => $minutesBefore,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    } else {
                        $this->info("    - Reminder already in cache for User ID {$user->id}, skipping");
                    }
                }
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Pre-session reminders processing completed.");
        $this->info("Total reminders sent: {$totalRemindersSent}");
        $this->info(str_repeat('=', 50));
        return Command::SUCCESS;
    }
}


