<?php

namespace App\Console\Commands;

use App\Models\Trade;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpireSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire trade sessions that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting session expiration process...');
        
        $expiredCount = 0;
        $now = Carbon::now();
        
        // Find trades that should be expired
        $tradesToExpire = Trade::where('status', 'ongoing')
            ->where(function($query) use ($now) {
                $query->where('end_date', '<', $now->toDateString())
                      ->orWhere(function($q) use ($now) {
                          // Also expire trades where end_date is today but time has passed
                          $q->whereDate('end_date', $now->toDateString())
                            ->where('available_to', '<', $now->toTimeString());
                      });
            })
            ->get();
        
        foreach ($tradesToExpire as $trade) {
            try {
                // Update trade status to closed
                $trade->update(['status' => 'closed']);
                
                // Log the expiration
                Log::info("Trade session expired", [
                    'trade_id' => $trade->id,
                    'user_id' => $trade->user_id,
                    'end_date' => $trade->end_date,
                    'expired_at' => $now
                ]);
                
                $expiredCount++;
                
            } catch (\Exception $e) {
                Log::error("Failed to expire trade session", [
                    'trade_id' => $trade->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("Expired {$expiredCount} trade sessions.");
        
        if ($expiredCount > 0) {
            Log::info("Session expiration completed", [
                'expired_count' => $expiredCount,
                'timestamp' => $now
            ]);
        }
        
        return 0;
    }
}