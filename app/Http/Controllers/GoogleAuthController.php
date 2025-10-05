<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Mail;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        $isRegistration = $request->query('registration', false);
        
        // Store registration flag in session
        if ($isRegistration) {
            session(['google_registration' => true]);
        }
        
        return Socialite::driver('google')
            ->redirect();
    }
    
    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if this is a registration flow
            $isRegistration = session('google_registration', false);
            session()->forget('google_registration');
            
            // Find existing user by email
            $user = User::where('email', $googleUser->getEmail())->first();
            
            if ($user) {
                // User exists - log them in
                Auth::login($user);
                
                return redirect()->intended('/dashboard')
                    ->with('status', 'Welcome back! You have been logged in successfully.');
            } else {
                if ($isRegistration) {
                    // Create new user account
                    $user = $this->createUserFromGoogle($googleUser);
                    Auth::login($user);
                    
                    return redirect()->route('profile.edit')
                        ->with('status', 'Account created successfully! Please complete your profile.');
                } else {
                    // User doesn't exist and this is not a registration
                    return redirect()->route('login')
                        ->with('error', 'No account found with this email. Please register first.');
                }
            }
            
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Google authentication failed. Please try again.');
        }
    }
    
    /**
     * Create user from Google OAuth data
     */
    private function createUserFromGoogle($googleUser)
    {
        // Generate a username from email
        $email = $googleUser->getEmail();
        $username = Str::before($email, '@') . '_' . Str::random(4);
        
        // Ensure username is unique
        while (User::where('username', $username)->exists()) {
            $username = Str::before($email, '@') . '_' . Str::random(4);
        }
        
        // Get first skill as default
        $defaultSkill = Skill::first();
        
        $user = User::create([
            'firstname' => $googleUser->getName() ?: 'User',
            'lastname' => '',
            'email' => $email,
            'username' => $username,
            'password' => Hash::make(Str::random(32)), // Random password since they'll use Google
            'role' => 'user',
            'plan' => 'free',
            'token_balance' => 0,
            'is_verified' => false, // Admin approval still required
            'email_verified_at' => now(), // Google email is already verified
            'skill_id' => $defaultSkill ? $defaultSkill->skill_id : null,
        ]);
        
        // Attach default skill
        if ($defaultSkill) {
            $user->skills()->attach($defaultSkill->skill_id);
        }
        
        // Send welcome email
        $this->sendWelcomeEmail($user);
        
        return $user;
    }
    
    /**
     * Send welcome email to new user
     */
    private function sendWelcomeEmail($user)
    {
        try {
            Mail::send('emails.welcome', [
                'user' => $user,
                'loginUrl' => route('login')
            ], function ($message) use ($user) {
                $message->to($user->email, $user->firstname)
                        ->subject('Welcome to SkillsXchange!');
            });
        } catch (\Exception $e) {
            // Log error but don't fail registration
            \Log::error('Welcome email failed: ' . $e->getMessage());
        }
    }
}