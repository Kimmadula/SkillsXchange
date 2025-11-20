<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // For AJAX/JSON requests, return null to trigger 401 JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return null;
        }
        
        return route('login');
    }
    
    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        // Log authentication failures for debugging
        if ($request->is('chat/*')) {
            \Log::info('Authentication failed', [
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'exception' => 'Unauthenticated.'
            ]);
        }
        
        parent::unauthenticated($request, $guards);
    }
}
