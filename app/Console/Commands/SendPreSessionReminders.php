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

        // Windows: interpret times from Trade (start_date + available_from)
        $windows = [60, 30, 15]; // minutes

        foreach ($windows as $minutesBefore) {
            $targetTime = $now->copy()->addMinutes($minutesBefore);

            // Find trades starting at target time window (±5 minutes tolerance)
            $query = Trade::where('status', 'ongoing')
                ->whereDate('start_date', $targetTime->toDateString())
                ->where('available_from', '>=', $targetTime->copy()->subMinutes(5)->toTimeString())
                ->where('available_from', '<=', $targetTime->copy()->addMinutes(5)->toTimeString());

            $trades = $query->get();

            foreach ($trades as $trade) {
                // Notify trade owner and accepted requester if present
                $recipients = collect();
                if ($trade->user) $recipients->push($trade->user);
                $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
                if ($acceptedRequest && $acceptedRequest->requester) {
                    $recipients->push($acceptedRequest->requester);
                }

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
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Pre-session reminder notify failed', [
                                'trade_id' => $trade->id,
                                'user_id' => $user->id,
                                'minutes_before' => $minutesBefore,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        $this->info('Pre-session reminders processing completed.');
        return Command::SUCCESS;
    }
}


