<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpirePremiumSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'premium:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire premium subscriptions that have passed their expiration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting premium subscription expiration process...');

        $expiredCount = 0;
        $now = Carbon::now();

        // Find users with expired premium subscriptions
        $expiredUsers = User::where('plan', 'premium')
            ->whereNotNull('premium_expires_at')
            ->where('premium_expires_at', '<=', $now)
            ->get();

        foreach ($expiredUsers as $user) {
            try {
                // Downgrade to free plan
                $user->update([
                    'plan' => 'free',
                    'premium_expires_at' => null
                ]);

                // Log the expiration
                Log::info("Premium subscription expired", [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'expired_at' => $user->getOriginal('premium_expires_at'),
                    'downgraded_at' => $now
                ]);

                $expiredCount++;

            } catch (\Exception $e) {
                Log::error("Failed to expire premium subscription", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Expired {$expiredCount} premium subscription(s).");

        if ($expiredCount > 0) {
            Log::info("Premium expiration completed", [
                'expired_count' => $expiredCount,
                'timestamp' => $now
            ]);
        }

        return 0;
    }
}

