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

        // Add security headers to help establish trust with security tools
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions Policy - Allow camera and microphone for video calls
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(self), camera=(self), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()');
        
        // Content Security Policy - Allow necessary external resources and video call functionality
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.bunny.net https://js.pusher.com https://www.gstatic.com https://*.firebaseio.com https://*.firebasedatabase.app https://*.googleapis.com; " .
               "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.bunny.net; " .
               "img-src 'self' data: https: blob:; " .
               "font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.bunny.net; " .
               "connect-src 'self' wss: https: wss://*.pusher.com wss://*.pusherapp.com https://skillxchange.metered.live https://stun.l.google.com https://stun1.l.google.com https://stun.relay.metered.ca https://asia.relay.metered.ca https://*.firebaseio.com https://*.firebasedatabase.app; " .
               "media-src 'self' blob:; " .
               "frame-src 'self';";
        
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Add a custom header to identify this as a legitimate application
        $response->headers->set('X-Application-Name', 'SkillsXchangee - Skill Exchange Platform');
        $response->headers->set('X-Application-Version', '1.0.0');
        $response->headers->set('X-Application-Type', 'Educational Platform');

        return $response;
    }
}
