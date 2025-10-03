<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DDoSProtectionMiddleware
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
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $path = $request->path();
        
        // Check for suspicious patterns
        if ($this->isSuspiciousRequest($request)) {
            Log::warning('Suspicious request detected', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'url' => $request->fullUrl(),
                'path' => $path
            ]);
            
            return response()->json([
                'error' => 'Request blocked for security reasons'
            ], 403);
        }
        
        // Check for rapid requests from same IP
        if ($this->isRapidRequest($ip)) {
            Log::warning('Rapid requests detected', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'error' => 'Too many requests. Please slow down.'
            ], 429);
        }
        
        // Check for bot-like behavior (but allow health checks and legitimate requests)
        if ($this->isBotLikeBehavior($request) && !$this->isLegitimateRequest($request)) {
            Log::info('Bot-like behavior detected', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'url' => $request->fullUrl()
            ]);
            
            // Allow but log for monitoring
        }
        
        return $next($request);
    }
    
    /**
     * Check if the request is suspicious.
     */
    protected function isSuspiciousRequest(Request $request)
    {
        $path = $request->path();
        $userAgent = $request->userAgent();
        $query = $request->query();
        
        // Check for common attack patterns
        $suspiciousPatterns = [
            '/\.\./',  // Directory traversal
            '/<script/i',  // XSS attempts
            '/union\s+select/i',  // SQL injection
            '/drop\s+table/i',  // SQL injection
            '/exec\s*\(/i',  // Command injection
            '/eval\s*\(/i',  // Code injection
            '/javascript:/i',  // JavaScript injection
            '/vbscript:/i',  // VBScript injection
            '/onload=/i',  // Event handler injection
            '/onerror=/i',  // Event handler injection
        ];
        
        $fullUrl = $request->fullUrl();
        $postData = $request->all();
        
        // Check URL and POST data for suspicious patterns
        $dataToCheck = array_merge([$fullUrl], array_values($query), array_values($postData));
        
        foreach ($dataToCheck as $data) {
            if (is_string($data)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $data)) {
                        return true;
                    }
                }
            }
        }
        
        // Check for suspicious user agents
        $suspiciousUserAgents = [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'zap',
            'burp',
            'w3af',
            'acunetix',
            'nessus',
            'openvas',
            'skipfish',
            'dirb',
            'gobuster',
            'wfuzz',
            'ffuf'
        ];
        
        if ($userAgent) {
            foreach ($suspiciousUserAgents as $suspicious) {
                if (stripos($userAgent, $suspicious) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check for rapid requests from the same IP.
     */
    protected function isRapidRequest($ip)
    {
        $key = "rapid_requests_{$ip}";
        $requests = Cache::get($key, 0);
        
        // Allow 500 requests per minute for normal usage
        if ($requests > 500) {
            return true;
        }
        
        Cache::put($key, $requests + 1, 60); // 1 minute
        return false;
    }
    
    /**
     * Check for bot-like behavior.
     */
    protected function isBotLikeBehavior(Request $request)
    {
        $userAgent = $request->userAgent();
        
        if (!$userAgent) {
            return true; // No user agent is suspicious
        }
        
        // Check for common bot patterns
        $botPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/java/i',
            '/go-http/i',
            '/okhttp/i'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        // Check for missing common browser headers
        $browserHeaders = ['Accept', 'Accept-Language', 'Accept-Encoding'];
        $missingHeaders = 0;
        
        foreach ($browserHeaders as $header) {
            if (!$request->hasHeader($header)) {
                $missingHeaders++;
            }
        }
        
        // If more than 2 browser headers are missing, likely a bot
        return $missingHeaders > 2;
    }
    
    /**
     * Check if the request is legitimate (health checks, etc.).
     */
    protected function isLegitimateRequest(Request $request)
    {
        $path = $request->path();
        $userAgent = $request->userAgent();
        
        // Allow health checks and monitoring endpoints
        $legitimatePaths = [
            'health',
            'ping',
            'security-test',
            'test-db',
            'debug'
        ];
        
        foreach ($legitimatePaths as $legitimatePath) {
            if (str_contains($path, $legitimatePath)) {
                return true;
            }
        }
        
        // Allow requests from Render's internal IPs (health checks)
        $ip = $request->ip();
        if ($ip === '127.0.0.1' || $ip === '10.209.27.179') {
            return true;
        }
        
        // Allow requests with proper browser user agents
        if ($userAgent && (
            str_contains($userAgent, 'Mozilla') ||
            str_contains($userAgent, 'Chrome') ||
            str_contains($userAgent, 'Safari') ||
            str_contains($userAgent, 'Firefox') ||
            str_contains($userAgent, 'Edge')
        )) {
            return true;
        }
        
        return false;
    }
}
