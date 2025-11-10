<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillAcquisitionHistory;
use App\Models\User;
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

        $skills = $query->paginate(20);
        $skills->appends($request->query());

        // Get categories for filter dropdown
        $categories = Skill::select('category')->distinct()->pluck('category');

        return view('skills.index', compact('skills', 'categories'));
    }

    /**
     * Show the form for creating a new skill - DISABLED
     */
    public function create()
    {
        // Skills are now static - no creation allowed
        return redirect()->route('skills.index')
            ->with('error', 'Skill creation is no longer available. Skills are now static.');
    }

    /**
     * Store a newly created skill - DISABLED
     */
    public function store(Request $request)
    {
        // Skills are now static - no creation allowed
        return redirect()->route('skills.index')
            ->with('error', 'Skill creation is no longer available. Skills are now static.');
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
            /** @var User $user */
            $user = Auth::user();
            $userHasSkill = $user->skills()->where('user_skills.skill_id', $skill->skill_id)->exists();
        }

        return view('skills.show', compact('skill', 'relatedSkills', 'userHasSkill'));
    }

    /**
     * Show the form for editing the specified skill
     */
    public function edit(Skill $skill)
    {
        // Skills are now static - no editing allowed
        return redirect()->route('skills.show', $skill->skill_id)
            ->with('error', 'Skill editing is no longer available. Skills are now static.');
    }

    /**
     * Update the specified skill - DISABLED
     */
    public function update(Request $request, Skill $skill)
    {
        // Skills are now static - no updating allowed
        return redirect()->route('skills.show', $skill->skill_id)
            ->with('error', 'Skill editing is no longer available. Skills are now static.');
    }

    /**
     * Remove the specified skill - DISABLED
     */
    public function destroy(Skill $skill)
    {
        // Skills are now static - no deletion allowed
        return redirect()->route('skills.index')
            ->with('error', 'Skill deletion is no longer available. Skills are now static.');
    }

    /**
     * Add skill to current user's profile
     */
    public function addToProfile(Request $request, Skill $skill)
    {
        /** @var User $user */
        $user = Auth::user();

        // Check if user already has this skill
        if ($user->skills()->where('user_skills.skill_id', $skill->skill_id)->exists()) {
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
        /** @var User $user */
        $user = Auth::user();

        // Check if user has this skill
        if (!$user->skills()->where('user_skills.skill_id', $skill->skill_id)->exists()) {
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
