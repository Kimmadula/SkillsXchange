<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class HandleDomainChange
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
        // Check if this is a domain change scenario
        $currentDomain = $request->getHost();
        $oldDomain = 'skillsxchange-crus.onrender.com';
        $newDomain = 'skillsxchange.site';
        
        // If user is coming from old domain or has old session data
        if ($this->hasOldDomainSession($request)) {
            Log::info('Domain change detected', [
                'from' => $oldDomain,
                'to' => $currentDomain,
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);
            
            // Clear old session data
            $this->clearOldSessionData($request);
            
            // Regenerate session for new domain
            Session::regenerate();
            
            // Add a flag to indicate domain change
            Session::put('domain_migrated', true);
            Session::put('migration_time', time());
        }
        
        return $next($request);
    }
    
    /**
     * Check if request has old domain session data
     */
    private function hasOldDomainSession(Request $request)
    {
        // Check for old domain in referer
        $referer = $request->header('referer');
        if ($referer && str_contains($referer, 'skillsxchange-crus.onrender.com')) {
            return true;
        }
        
        // Check for old session cookie names
        $oldCookieName = 'laravel_session';
        $newCookieName = config('session.cookie');
        
        if ($request->hasCookie($oldCookieName) && !$request->hasCookie($newCookieName)) {
            return true;
        }
        
        // Check if session is invalid (CSRF mismatch)
        if (Session::getId() && !Session::has('_token')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear old session data
     */
    private function clearOldSessionData(Request $request)
    {
        try {
            // Clear all session data
            Session::flush();
            
            // Clear old cookies
            $oldCookies = [
                'laravel_session',
                'XSRF-TOKEN',
                'skillsxchange_session'
            ];
            
            foreach ($oldCookies as $cookieName) {
                if ($request->hasCookie($cookieName)) {
                    // Set cookie to expire immediately
                    cookie()->queue(cookie($cookieName, '', -1));
                }
            }
            
            Log::info('Old session data cleared for domain migration');
            
        } catch (\Exception $e) {
            Log::error('Error clearing old session data: ' . $e->getMessage());
        }
    }
}
