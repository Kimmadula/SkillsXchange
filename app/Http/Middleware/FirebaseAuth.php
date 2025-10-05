<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
// Note: Firebase JWT classes are not installed, using simplified token verification

class FirebaseAuth
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
        $token = $request->bearerToken() ?? $request->cookie('firebase_token');
        
        if (!$token) {
            return response()->json(['error' => 'No Firebase token provided'], 401);
        }

        try {
            // Verify the Firebase token
            $decodedToken = $this->verifyFirebaseToken($token);
            
            if (!$decodedToken) {
                return response()->json(['error' => 'Invalid Firebase token'], 401);
            }

            // Find or create user
            $user = User::findByFirebaseUid($decodedToken['uid']);
            
            if (!$user) {
                // Create user from Firebase data
                $user = User::createOrUpdateFromFirebase($decodedToken);
            }

            // Log the user in
            Auth::login($user);

            // Add user info to request
            $request->merge(['firebase_user' => $decodedToken]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Token verification failed: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }

    /**
     * Verify Firebase ID token
     */
    private function verifyFirebaseToken($token)
    {
        try {
            // Simplified token verification for development
            // In production, use Firebase Admin SDK for proper verification
            $decoded = json_decode(base64_decode($token), true);
            
            if (isset($decoded['uid'])) {
                return [
                    'uid' => $decoded['uid'] ?? null,
                    'email' => $decoded['email'] ?? null,
                    'email_verified' => $decoded['email_verified'] ?? false,
                    'display_name' => $decoded['display_name'] ?? null,
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            // If decoding fails, return null
            return null;
        }
    }
}
