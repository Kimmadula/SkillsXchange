<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserOnlyMiddleware
{
    /**
     * Handle an incoming request.
     * Restrict admin users from accessing user-only functionality like trades.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role === 'admin') {
            // Redirect admin users to admin dashboard with a message
            return redirect()->route('admin.dashboard')->with('error', 'Admin users cannot access user trading functionality. Please use the admin panel to manage the system.');
        }
        
        return $next($request);
    }
}
