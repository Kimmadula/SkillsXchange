<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SessionExpirationMiddleware
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
        try {
            // Check if user is authenticated
            if (Auth::check()) {
                $userId = Auth::id();
                $sessionId = Session::getId();
                
                // Only check for explicit logout - no automatic expiration
                // Sessions will persist until user explicitly logs out
                
                // Check for concurrent sessions (optional security feature)
                if (config('session.prevent_concurrent_sessions', false)) {
                    $this->checkConcurrentSessions($userId, $sessionId);
                }
                
                // Update last activity timestamp for monitoring purposes only
                Session::put('last_activity', time());
                
                // Store session info for monitoring
                Session::put('user_id', $userId);
                Session::put('session_start', Session::get('session_start', time()));
                
                // Mark session as persistent (no expiration)
                Session::put('persistent_session', true);
            }
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('SessionExpirationMiddleware Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'session_id' => Session::getId(),
                'url' => $request->url(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If there's an error in session handling, force logout for security
            Auth::logout();
            Session::flush();
            
            return redirect()->route('login')
                ->with('error', 'A session error occurred. Please log in again.');
        }
    }
    
    /**
     * Clear user-specific cache data
     */
    private function clearUserCache($userId)
    {
        try {
            // Clear any cached user data
            Cache::forget('user_' . $userId . '_data');
            Cache::forget('user_' . $userId . '_permissions');
            Cache::forget('user_' . $userId . '_notifications');
            
            // Clear any session-specific cache
            $sessionId = Session::getId();
            Cache::forget('session_' . $sessionId . '_data');
            
        } catch (\Exception $e) {
            Log::warning('Failed to clear user cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Check for concurrent sessions (security feature)
     */
    private function checkConcurrentSessions($userId, $currentSessionId)
    {
        try {
            $allowedSessions = config('session.max_concurrent_sessions', 1);
            $sessionKey = 'user_sessions_' . $userId;
            
            // Get current user sessions
            $userSessions = Cache::get($sessionKey, []);
            
            // Remove expired sessions
            $userSessions = array_filter($userSessions, function($session) {
                return $session['last_activity'] > (time() - (config('session.lifetime', 60) * 60));
            });
            
            // Check if current session is in the list
            if (!isset($userSessions[$currentSessionId])) {
                // This is a new session, check if we exceed the limit
                if (count($userSessions) >= $allowedSessions) {
                    // Remove oldest session
                    uasort($userSessions, function($a, $b) {
                        return $a['last_activity'] <=> $b['last_activity'];
                    });
                    $oldestSession = array_key_first($userSessions);
                    unset($userSessions[$oldestSession]);
                }
            }
            
            // Add/update current session
            $userSessions[$currentSessionId] = [
                'last_activity' => time(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ];
            
            // Store updated sessions
            Cache::put($sessionKey, $userSessions, config('session.lifetime', 60) * 60);
            
        } catch (\Exception $e) {
            Log::warning('Failed to check concurrent sessions: ' . $e->getMessage());
        }
    }
}
