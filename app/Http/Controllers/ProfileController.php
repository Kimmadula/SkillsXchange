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
            
            return view('profile.show', [
                'user' => $user,
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
            $user = $request->user();
            
            if (!$user) {
                abort(404, 'User not found');
            }
            
            $user->load(['skills']);
            $skills = Skill::all();
            
            return view('profile.edit', [
                'user' => $user,
                'skills' => $skills,
            ]);
        } catch (\Exception $e) {
            Log::error('Profile edit error: ' . $e->getMessage());
            abort(500, 'Error loading profile edit form');
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
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

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            Log::info('User data before save:', [
                'username' => $user->username,
                'email' => $user->email,
                'photo' => $user->photo,
                'isDirty' => $user->isDirty(),
                'changes' => $user->getChanges()
            ]);

            $user->save();
            
            Log::info('User saved successfully');

            return Redirect::route('profile.show')->with('status', 'profile-updated');
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return Redirect::back()->withErrors(['error' => 'Failed to update profile. Please try again.']);
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
            
            $request->validate([
                'current_password' => ['required', 'current-password'],
                'password' => ['required', 'confirmed', 'min:8'],
            ]);

            $user = $request->user();
            
            if (!$user) {
                abort(404, 'User not found');
            }

            $user->update([
                'password' => Hash::make($request->password),
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
