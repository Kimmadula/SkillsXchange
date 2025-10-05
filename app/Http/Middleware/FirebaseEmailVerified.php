<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FirebaseEmailVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('firebase.login');
        }

        // Check if user has Firebase UID (Firebase user)
        if ($user->firebase_uid) {
            // For Firebase users, check if email is verified
            if (!$user->is_verified) {
                // Redirect to email verification page
                return redirect()->route('firebase.verify-email')
                    ->with('error', 'Please verify your email address to continue.');
            }
        }

        return $next($request);
    }
}
