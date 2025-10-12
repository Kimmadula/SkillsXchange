<?php

namespace App\Providers;

use App\Mail\ResendTransportFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Resend;
use Resend\Client;
use Resend\Contracts\Client as ClientContract;
use Resend\Laravel\Exceptions\ApiKeyIsMissing;

class ResendServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('resend', function (array $config = []) {
            return new ResendTransportFactory($this->app['resend'], $config['options'] ?? []);
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->bindResendClient();
    }

    /**
     * Bind the Resend Client.
     */
    protected function bindResendClient(): void
    {
        $this->app->singleton(ClientContract::class, static function (): Client {
            $apiKey = config('resend.api_key');

            if (! is_string($apiKey)) {
                throw ApiKeyIsMissing::create();
            }

            return Resend::client($apiKey);
        });

        $this->app->alias(ClientContract::class, 'resend');
        $this->app->alias(ClientContract::class, Client::class);
    }
}
