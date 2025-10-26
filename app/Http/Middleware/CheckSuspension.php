<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuspension
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Check if user is suspended or banned
            if ($user->isAccountRestricted()) {
                $suspension = $user->getCurrentSuspension();
                $ban = $user->getCurrentBan();
                
                if ($ban) {
                    // User is permanently banned
                    auth()->logout();
                    return redirect()->route('login')->with('error', 'Your account has been permanently banned. Reason: ' . $ban->reason);
                }
                
                if ($suspension && $suspension->isSuspensionActive()) {
                    // User is suspended
                    $message = 'Your account is currently suspended.';
                    if ($suspension->suspension_end) {
                        $message .= ' Suspension ends on: ' . $suspension->suspension_end->format('M d, Y \a\t g:i A');
                    } else {
                        $message .= ' Suspension is indefinite.';
                    }
                    $message .= ' Reason: ' . $suspension->reason;
                    
                    auth()->logout();
                    return redirect()->route('login')->with('error', $message);
                }
            }
        }

        return $next($request);
    }
}