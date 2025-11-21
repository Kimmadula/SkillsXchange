<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register Pusher service
        $this->app->singleton('pusher', function ($app) {
            $options = config('broadcasting.connections.pusher.options');

            // Ensure cluster is set - it's required for Pusher
            if (empty($options['cluster'])) {
                \Log::warning('Pusher cluster not set, using default ap1');
                $options['cluster'] = 'ap1';
            }

            \Log::info('Initializing Pusher', [
                'key' => config('broadcasting.connections.pusher.key'),
                'app_id' => config('broadcasting.connections.pusher.app_id'),
                'cluster' => $options['cluster']
            ]);

            $pusher = new \Pusher\Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                $options
            );
            return $pusher;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
