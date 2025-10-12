<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Skill;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
 

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $skills = Skill::all();
        return view('auth.register', compact('skills'));
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Check for duplicate submission using a simple token mechanism
        $submissionKey = 'registration_' . $request->ip() . '_' . $request->username . '_' . $request->email;
        
        if (Cache::has($submissionKey)) {
            Log::warning('Duplicate registration attempt detected', [
                'ip' => $request->ip(),
                'username' => $request->username,
                'email' => $request->email,
                'user_agent' => $request->userAgent()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Registration is already in progress. Please wait a moment and try again.'])
                ->withInput();
        }
        
        // Set a temporary lock for 30 seconds to prevent duplicate submissions
        Cache::put($submissionKey, true, 30);
        
        $request->validate([
            'firstname' => ['required', 'string', 'max:50'],
            'middlename' => ['nullable', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female,other'],
            'bdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'address' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'photo' => ['nullable', 'image', 'max:2048'],
            'selected_skills' => ['required', 'string'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
        }

        // Parse selected skills
        $selectedSkills = json_decode($request->selected_skills, true);
        
        if (!$selectedSkills || !is_array($selectedSkills) || empty($selectedSkills)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['selected_skills' => 'Please select at least one skill.']);
        }

        // Validate that all skill IDs exist
        $skillIds = array_column($selectedSkills, 'id');
        $existingSkills = Skill::whereIn('skill_id', $skillIds)->pluck('skill_id')->toArray();
        
        if (count($skillIds) !== count($existingSkills)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['selected_skills' => 'One or more selected skills are invalid.']);
        }

        // Use database transaction to ensure data integrity
        try {
            DB::beginTransaction();
            
            $user = User::create([
                'firstname' => $request->firstname,
                'middlename' => $request->middlename,
                'lastname' => $request->lastname,
                'gender' => $request->gender,
                'bdate' => $request->bdate,
                'address' => $request->address,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'photo' => $photoPath,
                'skill_id' => $skillIds[0], // Keep the first skill as primary for backward compatibility
                'is_verified' => false, // Requires admin approval
                'email_verified_at' => null, // Email not verified yet - Laravel will handle this
                'role' => 'user',
                'plan' => 'free',
                'token_balance' => 0,
            ]);

            // Attach all selected skills to the user
            $user->skills()->attach($skillIds);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('User registration failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withErrors(['error' => 'Registration failed. Please try again.'])
                ->withInput();
        }

        event(new Registered($user));

        Auth::login($user);
        
        try {
            // Send email verification notification using Laravel's built-in system
            Log::info('Attempting to send email verification', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->firstname . ' ' . $user->lastname,
                'mail_config' => [
                    'driver' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                ],
                'email_destination' => 'TO: ' . $user->email . ' (user email address)',
                'email_sender' => 'FROM: ' . config('mail.from.address') . ' (system sender)'
            ]);
            
            $user->sendEmailVerificationNotification();
            
            Log::info('Email verification sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return redirect()->route('verification.notice')->with('status', 'Registration successful! Please check your email at ' . $user->email . ' and click the verification link to complete your registration. You will also need admin approval to access all features.');
        } catch (\Exception $e) {
            // If email sending fails, still allow registration but log the error
            Log::error('Email verification failed to send: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('verification.notice')->with('status', 'Registration successful! However, there was an issue sending the verification email. Please try logging in to resend the verification email, or contact support if the problem persists.');
        }
    }

}
