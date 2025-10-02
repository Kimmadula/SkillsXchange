<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);
        
        // Different rate limits for different types of requests
        $maxAttempts = $this->getMaxAttempts($request);
        $decayMinutes = $this->getDecayMinutes($request);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            // Log suspicious activity
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'seconds_remaining' => $seconds
            ]);
            
            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $seconds
            ], 429);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        return $next($request);
    }
    
    /**
     * Resolve the request signature.
     */
    protected function resolveRequestSignature(Request $request)
    {
        return sha1(
            $request->method() .
            '|' . $request->server('SERVER_NAME', '') .
            '|' . $request->path() .
            '|' . $request->ip()
        );
    }
    
    /**
     * Get the maximum number of attempts for the given request.
     */
    protected function getMaxAttempts(Request $request)
    {
        $path = $request->path();
        
        // API endpoints - more restrictive
        if (str_starts_with($path, 'api/')) {
            return 60; // 60 requests per hour
        }
        
        // Authentication endpoints - moderate restriction
        if (in_array($path, ['login', 'register', 'password/reset'])) {
            return 20; // 20 attempts per 5 minutes
        }
        
        // Video call endpoints - moderate
        if (str_contains($path, 'video-call')) {
            return 30; // 30 requests per 15 minutes
        }
        
        // Chat endpoints - very lenient for video calls
        if (str_contains($path, 'chat')) {
            // Check if it's a message polling request (GET)
            if ($request->isMethod('GET') && str_contains($path, 'messages')) {
                return 10000; // 10000 requests per 15 minutes for message polling (effectively unlimited)
            }
            // Regular chat messages
            return 500; // 500 requests per 15 minutes for sending messages
        }
        
        // General web requests - more lenient
        return 300; // 300 requests per 1 minute
    }
    
    /**
     * Get the number of minutes to decay the rate limiter.
     */
    protected function getDecayMinutes(Request $request)
    {
        $path = $request->path();
        
        // Authentication endpoints - shorter decay
        if (in_array($path, ['login', 'register', 'password/reset'])) {
            return 5; // 5 minutes
        }
        
        // API and video call endpoints - moderate decay
        if (str_starts_with($path, 'api/') || str_contains($path, 'video-call')) {
            return 15; // 15 minutes
        }
        
        // General requests - much shorter decay
        return 1; // 1 minute
    }
}
