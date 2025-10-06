<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FirebaseAuthController extends Controller
{
    /**
     * Handle Firebase authentication callback
     */
    public function callback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
            'provider' => 'required|string|in:email,google,facebook,twitter,github',
            'is_registration' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $firebaseToken = $request->input('firebase_token');
            $provider = $request->input('provider');
            $isRegistration = $request->input('is_registration', false);
            
            // Block Google registration - only allow Google login
            if ($provider === 'google' && $isRegistration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google registration is not available. Please use email registration or sign in with your existing Google account.'
                ], 403);
            }
            
            // Decode Firebase token (simplified for development)
            $firebaseUser = $this->decodeFirebaseToken($firebaseToken);
            
            if (!$firebaseUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Firebase token'
                ], 401);
            }

            // Find or create user
            $user = User::findByFirebaseUid($firebaseUser['uid']);
            $isNewUser = false;
            
            if (!$user) {
                // For Google login, create user but require profile completion
                if ($provider === 'google') {
                    $user = User::create([
                        'firebase_uid' => $firebaseUser['uid'],
                        'firebase_provider' => $provider,
                        'email' => $firebaseUser['email'],
                        'firstname' => $firebaseUser['display_name'] ? explode(' ', $firebaseUser['display_name'])[0] : null,
                        'lastname' => $firebaseUser['display_name'] ? substr($firebaseUser['display_name'], strpos($firebaseUser['display_name'], ' ') + 1) : null,
                        'photo' => $firebaseUser['photo_url'] ?? null,
                        'is_verified' => $firebaseUser['email_verified'] ?? false,
                        'role' => 'user',
                        'plan' => 'free',
                        'token_balance' => 0,
                        'google_verified' => true,
                        'email_verified_at' => now()
                    ]);
                    $isNewUser = true;
                } else {
                    $user = User::createOrUpdateFromFirebase($firebaseUser, $provider);
                    $isNewUser = true;
                }
            } else {
                // Update existing user with latest Firebase data
                $user->update([
                    'email' => $firebaseUser['email'] ?? $user->email,
                    'is_verified' => $firebaseUser['email_verified'] ?? $user->is_verified,
                ]);
            }

            // New user verification flow - allow all users to access dashboard
            if ($isNewUser || $isRegistration) {
                // Set user as verified by default (new flow)
                $user->update([
                    'is_verified' => true,
                    'google_verified' => true,
                    'email_verified_at' => now()
                ]);
            }

            // Log the user in
            Auth::login($user);

            // Determine redirect URL based on user status
            if ($isNewUser || $isRegistration) {
                // New users go to profile completion
                $redirectUrl = route('firebase.profile.complete');
            } else {
                // Check if existing user has complete profile
                if (!$user->firstname || !$user->lastname || !$user->address || !$user->username) {
                    // Incomplete profile - redirect to completion
                    $redirectUrl = route('firebase.profile.complete');
                } else {
                    // Complete profile - go to dashboard
                    $redirectUrl = route('dashboard');
                }
            }

            return response()->json([
                'success' => true,
                'message' => $isNewUser ? 'Registration successful' : 'Authentication successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_verified' => $user->is_verified,
                ],
                'redirect_url' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect_url' => route('login')
        ]);
    }

    /**
     * Get current user info
     */
    public function user(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_verified' => $user->is_verified,
                'firebase_uid' => $user->firebase_uid,
                'firebase_provider' => $user->firebase_provider,
            ]
        ]);
    }

    /**
     * Show profile completion page
     */
    public function showProfileComplete(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('firebase.login');
        }

        // Check if user is verified (middleware should handle this, but double-check)
        if ($user->firebase_uid && !$user->is_verified) {
            return redirect()->route('firebase.verify-email')
                ->with('error', 'Please verify your email address to continue.');
        }

        // Check if user already has complete profile
        if ($user->firstname && $user->lastname && $user->address) {
            return redirect()->route('dashboard');
        }

        $skills = \App\Models\Skill::all();
        
        // Extract Firebase user data
        $firebaseUser = [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'username' => $user->username,
        ];

        return view('auth.profile-complete', compact('skills', 'firebaseUser'));
    }

    /**
     * Complete Firebase user profile
     */
    public function completeProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('firebase.login');
        }

        $request->validate([
            'firstname' => ['required', 'string', 'max:50'],
            'middlename' => ['nullable', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female,other'],
            'bdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'address' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
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
        $existingSkills = \App\Models\Skill::whereIn('skill_id', $skillIds)->pluck('skill_id')->toArray();
        
        if (count($skillIds) !== count($existingSkills)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['selected_skills' => 'One or more selected skills are invalid.']);
        }

        // Update user profile
        $user->update([
            'firstname' => $request->firstname,
            'middlename' => $request->middlename,
            'lastname' => $request->lastname,
            'gender' => $request->gender,
            'bdate' => $request->bdate,
            'address' => $request->address,
            'username' => $request->username,
            'photo' => $photoPath ?: $user->photo,
            'skill_id' => $skillIds[0], // Keep the first skill as primary
            'is_verified' => false, // stays false until admin approves
            'role' => 'user',
            'plan' => 'free',
            'token_balance' => 0,
        ]);

        // Attach all selected skills to the user
        $user->skills()->sync($skillIds);

        return redirect()->route('dashboard')->with('status', 'Profile completed successfully! Your account is pending admin approval.');
    }

    /**
     * Handle Google sign-in with username requirement
     */
    public function googleCallback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
            'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_]+$/',
            'provider' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $firebaseToken = $request->input('firebase_token');
            $username = $request->input('username');
            $provider = $request->input('provider');
            
            $firebaseUser = $this->decodeFirebaseToken($firebaseToken);
            
            if (!$firebaseUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Firebase token'
                ], 401);
            }

            // Check if username already exists
            $existingUser = User::where('username', $username)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username already exists'
                ], 409);
            }

            // Check if user already exists with this Firebase UID
            $user = User::findByFirebaseUid($firebaseUser['uid']);
            
            if ($user) {
                // Update existing user with new username if it's different
                if ($user->username !== $username) {
                    $user->update(['username' => $username]);
                }
            } else {
                // Create new user from Firebase data
                $user = User::create([
                    'firebase_uid' => $firebaseUser['uid'],
                    'firebase_provider' => $provider,
                    'email' => $firebaseUser['email'],
                    'username' => $username,
                    'firstname' => $firebaseUser['display_name'] ? explode(' ', $firebaseUser['display_name'])[0] : null,
                    'lastname' => $firebaseUser['display_name'] ? substr($firebaseUser['display_name'], strpos($firebaseUser['display_name'], ' ') + 1) : null,
                    'photo' => $firebaseUser['photo_url'] ?? null,
                    'is_verified' => $firebaseUser['email_verified'] ?? false,
                    'role' => 'user',
                    'plan' => 'free',
                    'token_balance' => 0,
                ]);
            }

            // Log the user in
            Auth::login($user);

            return response()->json([
                'success' => true,
                'message' => 'Google sign-in completed successfully',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'redirect_url' => route('dashboard')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete Google sign-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user verification status from Firebase
     */
    public function updateVerificationStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $firebaseToken = $request->input('firebase_token');
            $firebaseUser = $this->decodeFirebaseToken($firebaseToken);
            
            if (!$firebaseUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Firebase token'
                ], 401);
            }

            // Find user and update verification status
            $user = User::findByFirebaseUid($firebaseUser['uid']);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Update verification status
            $user->update([
                'is_verified' => $firebaseUser['email_verified'] ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification status updated',
                'is_verified' => $user->is_verified,
                'redirect_url' => $user->is_verified ? route('firebase.profile.complete') : route('firebase.verify-email')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update verification status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decode Firebase token (simplified for development)
     * In production, use Firebase Admin SDK for proper verification
     */
    private function decodeFirebaseToken($token)
    {
        try {
            // For development, we'll decode the token as JSON
            // In production, you should use Firebase Admin SDK
            $decoded = json_decode(base64_decode($token), true);
            
            if (isset($decoded['uid']) && isset($decoded['email'])) {
                return $decoded;
            }
            
            // If the above fails, try to extract from a mock token structure
            return [
                'uid' => 'firebase_' . substr(md5($token), 0, 28),
                'email' => $decoded['email'] ?? 'user@example.com',
                'email_verified' => $decoded['email_verified'] ?? true,
                'display_name' => $decoded['display_name'] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
