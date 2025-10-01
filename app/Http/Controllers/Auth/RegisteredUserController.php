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
use Illuminate\Support\Facades\Hash;
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
            'is_verified' => false, // stays false until admin approves
            'role' => 'user',
            'plan' => 'free',
            'token_balance' => 0,
        ]);

        // Attach all selected skills to the user
        $user->skills()->attach($skillIds);

        event(new Registered($user));

        return redirect()->route('login')->with('status', 'Your registration is pending approval by an admin.');
    }
}
