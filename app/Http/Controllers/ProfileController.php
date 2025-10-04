<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = $request->user();
        
        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            
            $photoPath = $request->file('photo')->store('photos', 'public');
            $user->photo = $photoPath;
        }

        // Update basic profile information
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Handle skills update
        if ($request->has('selected_skills')) {
            $selectedSkills = json_decode($request->selected_skills, true);
            if ($selectedSkills && is_array($selectedSkills)) {
                $skillIds = array_column($selectedSkills, 'id');
                $user->skills()->sync($skillIds);
                
                // Update primary skill (first selected skill)
                if (!empty($skillIds)) {
                    $user->skill_id = $skillIds[0];
                    $user->save();
                }
            }
        }

        return Redirect::route('profile.show')->with('status', 'profile-updated');
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
