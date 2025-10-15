<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, \Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // Check if this is a domain migration issue
            if ($this->isDomainMigrationIssue($request)) {
                Log::warning('CSRF token mismatch due to domain migration', [
                    'url' => $request->url(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->header('referer')
                ]);
                
                // Clear session and regenerate token
                Session::flush();
                Session::regenerate();
                
                // Redirect to login with a message
                return redirect()->route('login')
                    ->with('error', 'Your session has expired due to domain change. Please log in again.');
            }
            
            // Re-throw the exception for other cases
            throw $e;
        }
    }
    
    /**
     * Check if this is a domain migration issue
     */
    private function isDomainMigrationIssue($request)
    {
        // Check if user is on the new domain
        $currentDomain = $request->getHost();
        $newDomain = 'skillsxchange.site';
        
        // Check if referer contains old domain
        $referer = $request->header('referer');
        $hasOldDomainReferer = $referer && str_contains($referer, 'skillsxchange-crus.onrender.com');
        
        // Check if this is a login attempt
        $isLoginAttempt = $request->is('login') || $request->is('*/login');
        
        return $currentDomain === $newDomain && ($hasOldDomainReferer || $isLoginAttempt);
    }
}
