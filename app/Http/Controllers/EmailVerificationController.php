<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmailVerificationController extends Controller
{
    /**
     * Verify email address
     */
    public function verify(Request $request)
    {
        $token = $request->query('token');
        
        if (!$token) {
            return redirect()->route('login')->with('error', 'Invalid verification link.');
        }
        
        $user = User::where('email_verification_token', $token)->first();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Invalid or expired verification link.');
        }
        
        // Check if token is not older than 24 hours
        if ($user->created_at->diffInHours(now()) > 24) {
            return redirect()->route('login')->with('error', 'Verification link has expired. Please register again.');
        }
        
        // Verify the email
        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null
        ]);
        
        return redirect()->route('login')->with('status', 'Email verified successfully! Your account is now active.');
    }
    
    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        if ($user->email_verified_at) {
            return redirect()->route('login')->with('status', 'Email is already verified.');
        }
        
        // Generate new token
        $verificationToken = \Illuminate\Support\Str::random(60);
        $user->update(['email_verification_token' => $verificationToken]);
        
        // Send verification email
        $this->sendVerificationEmail($user, $verificationToken);
        
        return redirect()->route('login')->with('status', 'Verification email sent! Please check your inbox.');
    }
    
    /**
     * Send verification email
     */
    private function sendVerificationEmail($user, $token)
    {
        $verificationUrl = route('email.verify', ['token' => $token]);
        
        \Illuminate\Support\Facades\Mail::send('emails.verify-email', [
            'user' => $user,
            'verificationUrl' => $verificationUrl
        ], function ($message) use ($user) {
            $message->to($user->email, $user->firstname . ' ' . $user->lastname)
                    ->subject('Verify Your Email Address - SkillsXchange');
        });
    }
}