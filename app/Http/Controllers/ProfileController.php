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
            
            // Track username editing (only if it has changed and hasn't been edited before)
            if (isset($validatedData['username']) && $user->username !== $user->getOriginal('username') && !$user->username_edited) {
                $user->username_edited = true;
            }
            
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
                // Track birth date editing (only if it has changed and hasn't been edited before)
                if ($user->bdate !== $user->getOriginal('bdate') && !$user->bdate_edited) {
                    $user->bdate_edited = true;
                }
                $user->bdate = $validatedData['bdate'];
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
            $request->validate([
                'photo' => 'required|image|max:2048'
            ]);

            $user = $request->user();
            
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

            return Redirect::route('profile.edit')->with('status', 'password-updated');
            
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

        return Redirect::to('/');
    }
}
