<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillAcquisitionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SkillController extends Controller
{
    /**
     * Display a listing of skills
     */
    public function index(Request $request)
    {
        $query = Skill::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('category', 'LIKE', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Sorting
        $sortBy = $request->get('sort', 'name');
        $sortOrder = $request->get('order', 'asc');
        
        if (in_array($sortBy, ['name', 'category', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $skills = $query->paginate(20)->withQueryString();
        
        // Get categories for filter dropdown
        $categories = Skill::select('category')->distinct()->pluck('category');

        return view('skills.index', compact('skills', 'categories'));
    }

    /**
     * Show the form for creating a new skill
     */
    public function create()
    {
        $categories = Skill::select('category')->distinct()->pluck('category');
        return view('skills.create', compact('categories'));
    }

    /**
     * Store a newly created skill
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:skills,name',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:100',
            'difficulty_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'prerequisites' => 'nullable|string|max:500'
        ]);

        try {
            $skill = Skill::create([
                'skill_id' => Str::uuid(),
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'difficulty_level' => $request->difficulty_level ?? 'beginner',
                'prerequisites' => $request->prerequisites
            ]);

            return redirect()->route('skills.show', $skill->skill_id)
                ->with('success', 'Skill created successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create skill: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified skill
     */
    public function show(Skill $skill)
    {
        $skill->load(['users']);
        
        // Get related skills in the same category
        $relatedSkills = Skill::where('category', $skill->category)
            ->where('skill_id', '!=', $skill->skill_id)
            ->limit(5)
            ->get();

        // Check if current user has this skill
        $userHasSkill = false;
        if (Auth::check()) {
            $userHasSkill = Auth::user()->skills()->where('skill_id', $skill->skill_id)->exists();
        }

        return view('skills.show', compact('skill', 'relatedSkills', 'userHasSkill'));
    }

    /**
     * Show the form for editing the specified skill
     */
    public function edit(Skill $skill)
    {
        $categories = Skill::select('category')->distinct()->pluck('category');
        return view('skills.edit', compact('skill', 'categories'));
    }

    /**
     * Update the specified skill
     */
    public function update(Request $request, Skill $skill)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:skills,name,' . $skill->skill_id . ',skill_id',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:100',
            'difficulty_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'prerequisites' => 'nullable|string|max:500'
        ]);

        try {
            $skill->update([
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'difficulty_level' => $request->difficulty_level ?? 'beginner',
                'prerequisites' => $request->prerequisites
            ]);

            return redirect()->route('skills.show', $skill->skill_id)
                ->with('success', 'Skill updated successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update skill: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified skill
     */
    public function destroy(Skill $skill)
    {
        try {
            // Check if skill is being used by any users
            $userCount = $skill->users()->count();
            if ($userCount > 0) {
                return redirect()->back()
                    ->with('error', "Cannot delete skill. It is currently possessed by {$userCount} user(s).");
            }

            $skill->delete();

            return redirect()->route('skills.index')
                ->with('success', 'Skill deleted successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete skill: ' . $e->getMessage());
        }
    }

    /**
     * Add skill to current user's profile
     */
    public function addToProfile(Request $request, Skill $skill)
    {
        $user = Auth::user();
        
        // Check if user already has this skill
        if ($user->skills()->where('skill_id', $skill->skill_id)->exists()) {
            return redirect()->back()
                ->with('error', 'You already have this skill in your profile.');
        }

        try {
            // Add skill to user
            $user->skills()->attach($skill->skill_id);

            // Record in skill acquisition history
            SkillAcquisitionHistory::recordSkillAcquisition(
                $user->id,
                $skill->skill_id,
                'manual_add',
                [
                    'notes' => 'Manually added to profile'
                ]
            );

            return redirect()->back()
                ->with('success', 'Skill added to your profile successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to add skill to profile: ' . $e->getMessage());
        }
    }

    /**
     * Remove skill from current user's profile
     */
    public function removeFromProfile(Request $request, Skill $skill)
    {
        $user = Auth::user();
        
        // Check if user has this skill
        if (!$user->skills()->where('skill_id', $skill->skill_id)->exists()) {
            return redirect()->back()
                ->with('error', 'You do not have this skill in your profile.');
        }

        try {
            // Remove skill from user
            $user->skills()->detach($skill->skill_id);

            return redirect()->back()
                ->with('success', 'Skill removed from your profile successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to remove skill from profile: ' . $e->getMessage());
        }
    }

    /**
     * Get skills for AJAX requests
     */
    public function getSkills(Request $request)
    {
        $query = Skill::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $skills = $query->select('skill_id', 'name', 'category')
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($skills);
    }
}
