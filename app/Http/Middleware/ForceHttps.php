<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS in production
        if (app()->environment('production') && !$request->secure()) {
            // Check if we're behind a proxy (like Render) that handles HTTPS
            $isBehindProxy = $request->header('X-Forwarded-Proto') === 'https' || 
                           $request->header('X-Forwarded-Ssl') === 'on' ||
                           $request->header('X-Forwarded-For') !== null;
            
            // If we're behind a proxy that handles HTTPS, don't redirect
            if ($isBehindProxy) {
                return $next($request);
            }
            
            // Only redirect if we're accessing via HTTP and not behind a proxy
            if ($request->getScheme() === 'http') {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }
        
        return $next($request);
    }
}
