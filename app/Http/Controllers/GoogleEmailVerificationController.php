<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class GoogleEmailVerificationController extends Controller
{
    /**
     * Redirect to Google for email verification
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->redirect();
    }
    
    /**
     * Handle Google callback for email verification
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = Auth::user();
            
            // Verify that the Google email matches the user's email
            if ($googleUser->getEmail() !== $user->email) {
                return redirect()->route('profile.edit')
                    ->with('error', 'Google email does not match your account email. Please use the same email address.');
            }
            
            // Update user's email verification status
            $user->update([
                'email_verified_at' => now(),
                'google_verified' => true
            ]);
            
            // Send confirmation email
            $this->sendVerificationConfirmation($user);
            
            return redirect()->route('profile.edit')
                ->with('status', 'Email verified successfully! You now have full access to all platform features.');
            
        } catch (\Exception $e) {
            return redirect()->route('profile.edit')
                ->with('error', 'Google verification failed. Please try again.');
        }
    }
    
    /**
     * Send verification confirmation email
     */
    private function sendVerificationConfirmation($user)
    {
        try {
            Mail::send('emails.verification-confirmation', [
                'user' => $user,
                'loginUrl' => route('login')
            ], function ($message) use ($user) {
                $message->to($user->email, $user->firstname . ' ' . $user->lastname)
                        ->subject('Email Verified Successfully - SkillsXchange');
            });
        } catch (\Exception $e) {
            \Log::error('Verification confirmation email failed: ' . $e->getMessage());
        }
    }
}