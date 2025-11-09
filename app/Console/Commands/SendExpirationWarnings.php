<?php

namespace App\Console\Commands;

use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendExpirationWarnings extends Command
{
    protected $signature = 'notifications:send-expiration-warnings';
    protected $description = 'Send warnings before sessions expire (e.g., 24, 12, 1 hours)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $this->info('Scanning for sessions approaching expiration at ' . $now->toDateTimeString());

        $windows = [24, 12, 1]; // hours

        foreach ($windows as $hoursBefore) {
            $targetTime = $now->copy()->addHours($hoursBefore);

            // A session expires at end_date + available_to
            // Find trades whose end window matches targetTime (±10 minutes)
            $trades = Trade::where('status', 'ongoing')
                ->whereDate('end_date', $targetTime->toDateString())
                ->where('available_to', '>=', $targetTime->copy()->subMinutes(10)->toTimeString())
                ->where('available_to', '<=', $targetTime->copy()->addMinutes(10)->toTimeString())
                ->get();

            foreach ($trades as $trade) {
                $recipients = collect();
                if ($trade->user) $recipients->push($trade->user);
                $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
                if ($acceptedRequest && $acceptedRequest->requester) {
                    $recipients->push($acceptedRequest->requester);
                }

                foreach ($recipients as $user) {
                    $cacheKey = "reminder:expiration:trade:{$trade->id}:user:{$user->id}:hrs:{$hoursBefore}";
                    if (Cache::add($cacheKey, true, now()->addDays(2))) { // prevent duplicates for 48h
                        try {
                            // Check if notification already exists
                            $exists = DB::table('user_notifications')
                                ->where('user_id', $user->id)
                                ->where('type', 'session_expiration_warning')
                                ->where('data', 'like', '%"trade_id":' . $trade->id . '%')
                                ->where('data', 'like', '%"hours_before":' . $hoursBefore . '%')
                                ->exists();

                            if (!$exists) {
                                DB::table('user_notifications')->insert([
                                    'user_id' => $user->id,
                                    'type' => 'session_expiration_warning',
                                    'data' => json_encode([
                                        'trade_id' => $trade->id,
                                        'hours_before' => $hoursBefore,
                                        'message' => "Your session will expire in {$hoursBefore} hours.",
                                        'end_date' => $trade->end_date,
                                        'available_to' => $trade->available_to,
                                        'offering_skill' => optional($trade->offeringSkill)->name,
                                        'looking_skill' => optional($trade->lookingSkill)->name,
                                    ]),
                                    'read' => false,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Expiration warning notify failed', [
                                'trade_id' => $trade->id,
                                'user_id' => $user->id,
                                'hours_before' => $hoursBefore,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        $this->info('Expiration warnings processing completed.');
        return Command::SUCCESS;
    }
}


