<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Don't register routes here - they're already registered in routes/web.php with proper middleware
        // Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
