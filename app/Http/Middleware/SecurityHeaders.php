<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
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
        $response = $next($request);

        // Essential Security Headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Strict Transport Security (HSTS) - Force HTTPS
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        
        // Permissions Policy - Allow necessary features for video calls
        $response->headers->set('Permissions-Policy', 
            'geolocation=(), ' .
            'microphone=(self), ' .
            'camera=(self), ' .
            'payment=(), ' .
            'usb=(), ' .
            'magnetometer=(), ' .
            'gyroscope=(), ' .
            'accelerometer=(), ' .
            'fullscreen=(self), ' .
            'display-capture=(self)'
        );
        
        // Comprehensive Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
               "https://cdnjs.cloudflare.com " .
               "https://cdn.jsdelivr.net " .
               "https://fonts.bunny.net " .
               "https://js.pusher.com " .
               "https://www.gstatic.com " .
               "https://*.firebaseio.com " .
               "https://*.firebasedatabase.app " .
               "https://*.googleapis.com " .
               "https://unpkg.com; " .
               "style-src 'self' 'unsafe-inline' " .
               "https://cdnjs.cloudflare.com " .
               "https://cdn.jsdelivr.net " .
               "https://fonts.bunny.net " .
               "https://fonts.googleapis.com; " .
               "img-src 'self' data: https: blob:; " .
               "font-src 'self' " .
               "https://cdnjs.cloudflare.com " .
               "https://cdn.jsdelivr.net " .
               "https://fonts.bunny.net " .
               "https://fonts.gstatic.com; " .
               "connect-src 'self' wss: https: " .
               "wss://*.pusher.com " .
               "wss://*.pusherapp.com " .
               "https://*.pusher.com " .
               "https://*.pusherapp.com " .
               "https://*.firebaseio.com " .
               "https://*.firebasedatabase.app " .
               "https://stun.l.google.com " .
               "https://stun1.l.google.com " .
               "https://stun2.l.google.com " .
               "https://stun3.l.google.com " .
               "https://stun4.l.google.com " .
               "https://turn.relay.metered.ca " .
               "https://asia.relay.metered.ca " .
               "https://europe.relay.metered.ca " .
               "https://us.relay.metered.ca; " .
               "media-src 'self' blob: data:; " .
               "frame-src 'self' https://*.pusher.com; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'; " .
               "frame-ancestors 'self'; " .
               "upgrade-insecure-requests;";
        
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Cross-Origin Resource Sharing (CORS) Headers
        $response->headers->set('Access-Control-Allow-Origin', $request->getSchemeAndHttpHost());
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
        
        // Application Identification Headers (for security tools)
        $response->headers->set('X-Application-Name', 'SkillsXchange - Educational Skill Exchange Platform');
        $response->headers->set('X-Application-Version', '1.0.0');
        $response->headers->set('X-Application-Type', 'Educational Platform');
        $response->headers->set('X-Application-Purpose', 'Skill Learning and Exchange');
        $response->headers->set('X-Content-Type', 'Educational Web Application');
        
        // Cache Control for security
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        // Additional security headers
        $response->headers->set('X-Download-Options', 'noopen');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        return $response;
    }
}
