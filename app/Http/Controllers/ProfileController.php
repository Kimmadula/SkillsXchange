<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show(Request $request): View
    {
        try {
            $user = $request->user();

            if (!$user) {
                abort(404, 'User not found');
            }

            $user->load(['skills', 'skill']);

            // Get all user skills (both registered and acquired)
            $allUserSkills = $user->skills;

            // Get acquired skills through trading for reference
            $acquiredSkills = $user->getAcquiredSkills();

            // Debug logging
            Log::info('Profile show - skills debug', [
                'user_id' => $user->id,
                'all_skills_count' => $allUserSkills ? $allUserSkills->count() : 'null',
                'acquired_skills_count' => $acquiredSkills ? $acquiredSkills->count() : 'null',
                'all_skills' => $allUserSkills ? $allUserSkills->pluck('name')->toArray() : 'null',
                'acquired_skills' => $acquiredSkills ? $acquiredSkills->pluck('name')->toArray() : 'null'
            ]);

            return view('profile.show', [
                'user' => $user,
                'acquiredSkills' => $allUserSkills, // Use all skills instead of just acquired ones
            ]);
        } catch (\Exception $e) {
            Log::error('Profile show error: ' . $e->getMessage());
            abort(500, 'Error loading profile');
        }
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        try {
            // Debug logging
            Log::info('Profile edit access attempt', [
                'user_authenticated' => Auth::check(),
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'url' => $request->url(),
                'headers' => $request->headers->all()
            ]);

            $user = $request->user();

            if (!$user) {
                Log::warning('Profile edit: User not found', [
                    'auth_check' => Auth::check(),
                    'user_id' => Auth::id(),
                    'session_id' => session()->getId()
                ]);
                abort(404, 'User not found');
            }

            $user->load(['skills']);
            $skills = Skill::all();

            Log::info('Profile edit: Successfully loaded', [
                'user_id' => $user->id,
                'skills_count' => $skills->count()
            ]);

            return view('profile.edit', [
                'user' => $user,
                'skills' => $skills,
            ]);
        } catch (\Exception $e) {
            Log::error('Profile edit error: ' . $e->getMessage(), [
                'user_authenticated' => Auth::check(),
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error loading profile edit form');
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request)
    {
        try {
            Log::info('Profile update request received', [
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'all_data' => $request->all()
            ]);

            $user = $request->user();

            if (!$user) {
                abort(404, 'User not found');
            }

            // After admin verification: only username can be changed, with password confirmation
            if ($user->is_verified) {
                $validatedLimited = $request->validate([
                    'username' => 'required|string|max:255|unique:users,username,' . $user->id,
                    'current_password' => ['required', 'current_password'],
                ]);

                $user->username = $validatedLimited['username'];

                $user->save();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Username updated successfully',
                        'user' => [
                            'username' => $user->username,
                        ]
                    ]);
                }

                return Redirect::route('profile.edit')->with('status', 'profile-updated');
            }

            // If not verified: proceed with normal editable fields (except email remains read-only)

            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }

                $photoPath = $request->file('photo')->store('photos', 'public');
                $user->photo = $photoPath;
            }

            // Update validated fields
            $validatedData = $request->validated();
            Log::info('Validated data for profile update:', $validatedData);

            // Update account information
            $user->username = $validatedData['username'];
            $user->email = $validatedData['email'];

            // Username can be edited unlimited times (no tracking flag)

            // Update personal information if provided
            if (isset($validatedData['firstname'])) {
                $user->firstname = $validatedData['firstname'];
            }
            if (isset($validatedData['lastname'])) {
                $user->lastname = $validatedData['lastname'];
            }
            if (isset($validatedData['middlename'])) {
                $user->middlename = $validatedData['middlename'];
            }
            if (isset($validatedData['gender'])) {
                $user->gender = $validatedData['gender'];
            }
            if (isset($validatedData['bdate'])) {
                // Unverified users can edit bdate freely; verified users cannot change bdate
                if (!$user->is_verified) {
                    $user->bdate = $validatedData['bdate'];
                }
            }
            if (isset($validatedData['address'])) {
                $user->address = $validatedData['address'];
            }

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            Log::info('User data before save:', [
                'username' => $user->username,
                'email' => $user->email,
                'photo' => $user->photo,
                'isDirty' => $user->isDirty(),
                'changes' => $user->getChanges(),
                'original_username' => $user->getOriginal('username'),
                'original_email' => $user->getOriginal('email')
            ]);

            $user->save();

            Log::info('User saved successfully', [
                'username_after_save' => $user->username,
                'email_after_save' => $user->email,
                'fresh_username' => $user->fresh()->username,
                'fresh_email' => $user->fresh()->email
            ]);

            // Check if this is an AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'middlename' => $user->middlename,
                        'gender' => $user->gender,
                        'bdate' => $user->bdate instanceof \Carbon\Carbon ? $user->bdate->format('Y-m-d') : null,
                        'address' => $user->address,
                        'username' => $user->username
                    ]
                ]);
            }

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile. Please try again.'
                ], 500);
            }

            return Redirect::back()->withErrors(['error' => 'Failed to update profile. Please try again.']);
        }
    }

    /**
     * Update the user's profile photo.
     */
    public function updatePhoto(Request $request)
    {
        try {
            // Disallow photo change for unverified accounts
            $user = $request->user();
            if (!$user || !$user->is_verified) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Profile photo cannot be changed until your account is admin verified.'
                    ], 403);
                }
                return response()->json(null)->setStatusCode(403);
            }

            $request->validate([
                'photo' => 'required|image|max:2048'
            ]);

            if (!$user) {
                abort(404, 'User not found');
            }

            // Delete old photo if exists
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            // Store new photo
            $photoPath = $request->file('photo')->store('photos', 'public');
            $user->photo = $photoPath;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'photo_url' => asset('storage/' . $photoPath)
            ]);

        } catch (\Exception $e) {
            Log::error('Profile photo update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile photo. Please try again.'
            ], 500);
        }
    }


    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        try {
            Log::info('Password update request received', [
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'all_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', 'current-password'],
                'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            ]);

            $user = $request->user();

            if (!$user) {
                abort(404, 'User not found');
            }

            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            Log::info('Password updated successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            return Redirect::back()->with('status', 'password-updated');

        } catch (\Exception $e) {
            Log::error('Password update error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return Redirect::back()->withErrors(['password' => 'Failed to update password. Please try again.']);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        // Delete user's photo if exists
        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('success', 'Your account has been deleted successfully.');
    }

    /**
     * Unified endpoint to handle profile updates, photo upload, and password change
     * via a single route for one-page setups.
     */
    public function saveUnified(Request $request)
    {
        try {
            $intent = $request->input('intent');

            if ($intent === 'photo' || $request->hasFile('photo')) {
                return $this->updatePhoto($request);
            }

            if ($intent === 'password' || $request->has('current_password') && $request->has('password')) {
                return $this->updatePassword($request);
            }

            // Default to profile info update
            $user = $request->user();
            if ($user && $user->is_verified) {
                // Verified users: only username can be changed with password confirmation
                $validated = $request->validate([
                    'username' => 'required|string|max:255|unique:users,username,' . $user->id,
                    'current_password' => ['required', 'current_password'],
                ]);

                $user->username = $validated['username'];
                $user->save();

                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Username updated successfully',
                        'user' => [
                            'name' => $user->name,
                            'email' => $user->email,
                            'username' => $user->username,
                        ]
                    ]);
                }

                return Redirect::route('profile.show')->with('status', 'profile-updated');
            }

            // Unverified users: allow full profile update (except email)
            if ($user) {
                $validated = $request->validate([
                    'firstname' => ['required', 'string', 'max:255'],
                    'lastname' => ['required', 'string', 'max:255'],
                    'middlename' => ['nullable', 'string', 'max:255'],
                    'gender' => ['required', 'in:male,female,other'],
                    'bdate' => ['nullable', 'date'],
                    'address' => ['nullable', 'string', 'max:255'],
                    'username' => 'required|string|max:255|unique:users,username,' . $user->id,
                ]);

                $user->firstname = $validated['firstname'];
                $user->lastname = $validated['lastname'];
                $user->middlename = $validated['middlename'] ?? null;
                $user->gender = $validated['gender'];
                if (isset($validated['bdate'])) {
                    $user->bdate = $validated['bdate'];
                }
                $user->address = $validated['address'] ?? null;
                $user->username = $validated['username'];
                $user->save();

                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Profile updated successfully',
                        'user' => [
                            'name' => $user->name,
                            'email' => $user->email,
                            'username' => $user->username,
                        ]
                    ]);
                }

                return Redirect::route('profile.show')->with('status', 'profile-updated');
            }

            // Fallback
            return Redirect::back()->withErrors(['error' => 'User not found']);

        } catch (\Exception $e) {
            Log::error('Unified profile save error: ' . $e->getMessage());

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save profile: ' . $e->getMessage()
                ], 500);
            }

            return Redirect::back()->withErrors(['error' => 'Failed to save profile.']);
        }
    }
}
