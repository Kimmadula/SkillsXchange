<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Skill;
use App\Models\Trade;
use App\Models\TradeRequest;
use App\Models\TradeFeeSetting;
use App\Models\FeeTransaction;

class TradeController extends Controller
{
    public function create()
    {
        $user = Auth::user();

        // Additional check to prevent admin users from accessing trade functionality
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Admin users cannot access user trading functionality.');
        }

        // Check if user is verified by admin
        if (!$user->is_verified) {
            // Still show the form but with a warning - the form will be disabled
            // This allows users to see what they need to do
        }

        // Get all skills for "looking for" dropdown
        $skills = Skill::orderBy('category')->orderBy('name')->get();

        // Get user's registered skills (from user_skills table)
        $registeredSkills = $user->skills()->orderBy('category')->orderBy('name')->get();

        // Get user's acquired skills (from skill_acquisition_history)
        $acquiredSkills = $user->getAcquiredSkills();

        // Merge registered and acquired skills, remove duplicates, and sort
        $userAllSkills = $registeredSkills
            ->merge($acquiredSkills)
            ->unique('skill_id')
            ->sortBy(function($skill) {
                return $skill->category . '|' . $skill->name;
            })
            ->values();

        return view('trades.create', compact('user', 'skills', 'userAllSkills'));
    }

    public function show(Trade $trade)
    {
        $user = Auth::user();

        // Check if user can view this trade
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Admin users cannot access user trading functionality.');
        }

        // Load trade with related data
        $trade->load(['offeringUser', 'offeringSkill', 'lookingSkill']);

        return view('trades.show', compact('trade', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Additional check to prevent admin users from creating trades
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Admin users cannot create trades.');
        }

        // Check if user is verified by admin before allowing trade creation
        if (!$user->is_verified) {
            return redirect()->route('trades.create')
                ->with('error', 'Your account must be verified by an admin before you can post trades. Please wait for admin approval.')
                ->withInput();
        }

        $validated = $request->validate([
            'offering_skill_id' => [
                'required',
                'exists:skills,skill_id',
                function ($attribute, $value, $fail) use ($user) {
                    // Check if user has this skill registered OR acquired
                    $hasRegistered = $user->skills()->where('user_skills.skill_id', $value)->exists();
                    $acquiredSkills = $user->getAcquiredSkills();
                    $hasAcquired = $acquiredSkills->pluck('skill_id')->contains($value);

                    if (!$hasRegistered && !$hasAcquired) {
                        $fail('You can only offer skills that you have registered or acquired in your profile.');
                    }
                },
            ],
            'looking_skill_id' => ['required', 'exists:skills,skill_id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'available_from' => ['nullable'],
            'available_to' => ['nullable'],
            'preferred_days' => ['nullable','array'],
            'gender_pref' => ['required','in:any,male,female'],
            'location' => ['nullable','string','max:255'],
            'session_type' => ['required','in:any,online,onsite'],
            'use_username' => ['sometimes','boolean'],
        ]);

        $trade = new Trade($validated);
        $trade->user_id = $user->id;
        $trade->preferred_days = $validated['preferred_days'] ?? [];
        $trade->use_username = (bool)($request->use_username ?? false);
        $trade->status = 'open';
        $trade->save();

        return redirect()->route('trades.manage')->with('success', 'Trade posted.');
    }

    public function matches()
    {
        $user = Auth::user();

        // Additional check to prevent admin users from accessing matches
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Admin users cannot access user trading functionality.');
        }

        // Require user has a skill on profile
        $userSkill = $user->skill;
        if (!$userSkill) {
            return view('trades.matches', [
                'trades' => collect([]),
                'noSkill' => true,
                'userOpenTrades' => collect([])
            ]);
        }

        // Get all user's open trades for filtering (check before requiring)
        $userOpenTrades = Trade::where('user_id', $user->id)
            ->where('status', 'open')
            ->with(['offeringSkill', 'lookingSkill'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        // Require user has posted at least one open trade before viewing matches
        if ($userOpenTrades->isEmpty()) {
            return view('trades.matches', [
                'trades' => collect([]),
                'noTradePosted' => true,
                'userOpenTrades' => collect([])
            ]);
        }

        // Get all open trades from other users
        $trades = Trade::with(['user', 'offeringSkill', 'lookingSkill'])
            ->where('user_id', '!=', $user->id)
            ->where('status', 'open')
            ->get()
            ->map(function($trade) use ($user) {
                // Check if this trade is compatible with user's skill (and find which user trade matches)
                $matchingUserTrade = $this->findMatchingUserTrade($trade, $user);
                $trade->is_compatible = ($matchingUserTrade !== null);

                // Store which user trade this match is for (for filtering)
                $trade->matching_user_trade_id = $matchingUserTrade ? $matchingUserTrade->id : null;

                // Calculate compatibility score using the specific matching trade
                $trade->compatibility_score = $this->calculateCompatibility($trade, $matchingUserTrade);

                // Build human-readable insights (Venn diagram style) - use the matching trade
                $trade->insights = $this->buildMatchInsights($trade, $user, $matchingUserTrade);

                // Check existing request relationships to prevent duplicates and to prompt the user
                $trade->has_incoming_request_from_user = TradeRequest::where('requester_id', $trade->user_id)
                    ->whereHas('trade', function($q) use ($user) { $q->where('user_id', $user->id); })
                    ->where('status', 'pending')
                    ->exists();

                $trade->has_outgoing_request_to_user = TradeRequest::where('requester_id', $user->id)
                    ->whereHas('trade', function($q) use ($trade) { $q->where('user_id', $trade->user_id); })
                    ->where('status', 'pending')
                    ->exists();

                // Partner rating stats
                $ratingRow = \DB::table('session_ratings')
                    ->selectRaw('AVG(overall_rating) as avg_rating, COUNT(*) as total_ratings')
                    ->where('rated_user_id', $trade->user_id)
                    ->first();
                $trade->partner_rating_avg = $ratingRow ? round((float)($ratingRow->avg_rating ?? 0), 2) : 0;
                $trade->partner_rating_count = $ratingRow ? (int)($ratingRow->total_ratings ?? 0) : 0;

                return $trade;
            })
            // Only show compatible trades
            ->filter(function($trade) {
                return (bool) ($trade->is_compatible ?? false);
            })
            // Highest score first
            ->sortByDesc('compatibility_score')
            ->values();

        // Create notifications for new matches (avoid duplicates)
        foreach ($trades as $match) {
            $exists = \DB::table('user_notifications')
                ->where('user_id', $user->id)
                ->where('type', 'match_found')
                ->where('data', 'like', '%"trade_id":' . $match->id . '%')
                ->exists();

            if (!$exists) {
                \DB::table('user_notifications')->insert([
                    'user_id' => $user->id,
                    'type' => 'match_found',
                    'data' => json_encode([
                        'trade_id' => $match->id,
                        'offering_skill' => $match->offeringSkill->name ?? 'Unknown Skill',
                        'looking_skill' => $match->lookingSkill->name ?? 'Unknown Skill',
                        'partner_username' => $match->user->username,
                        'partner_name' => trim(($match->user->firstname ?? '') . ' ' . ($match->user->lastname ?? '')),
                        'compatibility_score' => $match->compatibility_score,
                        'session_type' => $match->session_type,
                        'location' => $match->location,
                        'available_from' => $match->available_from,
                        'available_to' => $match->available_to,
                        'preferred_days' => $match->preferred_days,
                        'start_date' => $match->start_date,
                        'end_date' => $match->end_date,
                        'partner_rating_avg' => $match->partner_rating_avg,
                        'partner_rating_count' => $match->partner_rating_count,
                    ]),
                    'read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return view('trades.matches', compact('trades', 'userOpenTrades'));
    }

    /**
     * Return the viewer's latest open trade (used for matching context)
     */
    private function getUserOpenTrade($user)
    {
        return Trade::where('user_id', $user->id)
            ->where('status', 'open')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Build comprehensive comparison insights for why this trade matches the viewer
     * @param Trade $theirTrade The trade being viewed
     * @param User $user The user viewing the trade
     * @param Trade|null $matchingUserTrade The specific user trade that matches (if any)
     */
    private function buildMatchInsights($theirTrade, $user, $matchingUserTrade = null)
    {
        $insights = [];
        // Use the matching trade if provided, otherwise fallback to first open trade
        $myTrade = $matchingUserTrade ?: $this->getUserOpenTrade($user);
        $tradeOwner = $theirTrade->user;

        // Skills Exchange
        $theirOffering = $theirTrade->offeringSkill->name ?? 'Not specified';
        $theirLooking = $theirTrade->lookingSkill->name ?? 'Not specified';
        $myOffering = $myTrade ? ($myTrade->offeringSkill->name ?? 'Not specified') : 'Not specified';
        $myLooking = $myTrade ? ($myTrade->lookingSkill->name ?? 'Not specified') : 'Not specified';
        $skillsMatch = $myTrade && ($theirTrade->looking_skill_id == $myTrade->offering_skill_id) && ($theirTrade->offering_skill_id == $myTrade->looking_skill_id);
        $insights[] = [
            'label' => 'Skills Exchange',
            'status' => $skillsMatch ? 'good' : 'neutral',
            'their_value' => "Offering: {$theirOffering} | Looking: {$theirLooking}",
            'your_value' => "Offering: {$myOffering} | Looking: {$myLooking}",
            'match_detail' => $skillsMatch ? 'Perfect match!' : 'Skills align'
        ];

        // Gender Preference
        $theirGenderPref = $theirTrade->gender_pref ? ucfirst($theirTrade->gender_pref) : 'Any';
        $myGenderPref = $myTrade && $myTrade->gender_pref ? ucfirst($myTrade->gender_pref) : 'Any';
        $theirGender = ucfirst($tradeOwner->gender ?? 'Not specified');
        $myGender = ucfirst($user->gender ?? 'Not specified');
        $genderMatch1 = !$theirTrade->gender_pref || $theirTrade->gender_pref === 'any' || ($user->gender && $theirTrade->gender_pref === $user->gender);
        $genderMatch2 = !$myTrade || !$myTrade->gender_pref || $myTrade->gender_pref === 'any' || ($tradeOwner->gender && $myTrade->gender_pref === $tradeOwner->gender);
        $genderOk = $genderMatch1 && $genderMatch2;
        $insights[] = [
            'label' => 'Gender Preference',
            'status' => $genderOk ? 'good' : ($genderMatch1 || $genderMatch2 ? 'neutral' : 'bad'),
            'their_value' => "Prefers: {$theirGenderPref} | Their gender: {$theirGender}",
            'your_value' => "You prefer: {$myGenderPref} | Your gender: {$myGender}",
            'match_detail' => $genderOk ? 'Both preferences match' : ($genderMatch1 || $genderMatch2 ? 'Partial match' : 'No match')
        ];

        // Session Type
        $theirSession = ucfirst($theirTrade->session_type ?? 'any');
        $mySession = $myTrade ? ucfirst($myTrade->session_type ?? 'any') : 'Any';
        $sessionMatch = !$myTrade || $theirTrade->session_type === 'any' || $myTrade->session_type === 'any' || $theirTrade->session_type === $myTrade->session_type;
        $insights[] = [
            'label' => 'Session Type',
            'status' => $sessionMatch ? 'good' : 'neutral',
            'their_value' => $theirSession,
            'your_value' => $mySession,
            'match_detail' => $sessionMatch ? 'Compatible' : 'Different preferences'
        ];

        // Location
        $theirLocation = $theirTrade->location ?: ($tradeOwner->address ?? 'Not specified');
        $myLocation = $myTrade && $myTrade->location ? $myTrade->location : ($user->address ?? 'Not specified');
        $locationMatch = false;
        if ($theirTrade->location && ($myTrade && $myTrade->location)) {
            $locationMatch = str_contains(strtolower($myTrade->location), strtolower($theirTrade->location))
                || str_contains(strtolower($theirTrade->location), strtolower($myTrade->location));
        } elseif ($theirTrade->location && $user->address) {
            $locationMatch = str_contains(strtolower($user->address), strtolower($theirTrade->location))
                || str_contains(strtolower($theirTrade->location), strtolower($user->address));
        } elseif ($myTrade && $myTrade->location && $tradeOwner->address) {
            $locationMatch = str_contains(strtolower($tradeOwner->address), strtolower($myTrade->location))
                || str_contains(strtolower($myTrade->location), strtolower($tradeOwner->address));
        }
        $insights[] = [
            'label' => 'Location',
            'status' => $locationMatch ? 'good' : 'neutral',
            'their_value' => $theirLocation,
            'your_value' => $myLocation,
            'match_detail' => $locationMatch ? 'Same area' : 'Different locations'
        ];

        // Time Availability
        $theirTime = $theirTrade->available_from && $theirTrade->available_to
            ? "{$theirTrade->available_from} - {$theirTrade->available_to}"
            : 'Flexible';
        $myTime = $myTrade && $myTrade->available_from && $myTrade->available_to
            ? "{$myTrade->available_from} - {$myTrade->available_to}"
            : 'Flexible';
        $timeMatch = false;
        $timeDetail = 'No overlap';
        if ($theirTrade->available_from && $theirTrade->available_to && $myTrade && $myTrade->available_from && $myTrade->available_to) {
            $tradeStart = $this->timeToMinutes($theirTrade->available_from);
            $tradeEnd = $this->timeToMinutes($theirTrade->available_to);
            $userStart = $this->timeToMinutes($myTrade->available_from);
            $userEnd = $this->timeToMinutes($myTrade->available_to);
            $overlap = min($tradeEnd, $userEnd) - max($tradeStart, $userStart);
            if ($overlap > 0) {
                $timeMatch = true;
                $timeDetail = floor($overlap) . ' minutes overlap';
            } else {
                $gap = min(abs($tradeStart - $userEnd), abs($userStart - $tradeEnd));
                $timeDetail = $gap <= 120 ? 'Close times (within 2h)' : 'Different times';
            }
        } elseif ($theirTrade->available_from && $theirTrade->available_to || ($myTrade && $myTrade->available_from && $myTrade->available_to)) {
            $timeMatch = true;
            $timeDetail = 'One is flexible';
        }
        $insights[] = [
            'label' => 'Time Availability',
            'status' => $timeMatch ? 'good' : 'neutral',
            'their_value' => $theirTime,
            'your_value' => $myTime,
            'match_detail' => $timeDetail
        ];

        // Preferred Days
        $theirDays = $theirTrade->preferred_days && is_array($theirTrade->preferred_days)
            ? implode(', ', $theirTrade->preferred_days)
            : 'Flexible';
        $myDays = $myTrade && $myTrade->preferred_days && is_array($myTrade->preferred_days)
            ? implode(', ', $myTrade->preferred_days)
            : 'Flexible';
        $daysMatch = false;
        $daysDetail = 'No overlap';
        if ($theirTrade->preferred_days && is_array($theirTrade->preferred_days) && $myTrade && $myTrade->preferred_days && is_array($myTrade->preferred_days)) {
            $overlapDays = array_intersect($theirTrade->preferred_days, $myTrade->preferred_days);
            if (count($overlapDays) > 0) {
                $daysMatch = true;
                $daysDetail = implode(', ', $overlapDays) . ' match';
            } else {
                $daysDetail = 'Different days';
            }
        } elseif ($theirTrade->preferred_days && is_array($theirTrade->preferred_days) || ($myTrade && $myTrade->preferred_days && is_array($myTrade->preferred_days))) {
            $daysMatch = true;
            $daysDetail = 'One is flexible';
        }
        $insights[] = [
            'label' => 'Preferred Days',
            'status' => $daysMatch ? 'good' : 'neutral',
            'their_value' => $theirDays,
            'your_value' => $myDays,
            'match_detail' => $daysDetail
        ];

        // Date Range
        $theirDates = $theirTrade->start_date && $theirTrade->end_date
            ? \Carbon\Carbon::parse($theirTrade->start_date)->format('M d') . ' - ' . \Carbon\Carbon::parse($theirTrade->end_date)->format('M d, Y')
            : ($theirTrade->start_date ? \Carbon\Carbon::parse($theirTrade->start_date)->format('M d, Y') . ' onwards' : 'Flexible');
        $myDates = $myTrade && $myTrade->start_date && $myTrade->end_date
            ? \Carbon\Carbon::parse($myTrade->start_date)->format('M d') . ' - ' . \Carbon\Carbon::parse($myTrade->end_date)->format('M d, Y')
            : ($myTrade && $myTrade->start_date ? \Carbon\Carbon::parse($myTrade->start_date)->format('M d, Y') . ' onwards' : 'Flexible');
        $datesMatch = false;
        $datesDetail = 'No overlap';
        if ($theirTrade->start_date && $theirTrade->end_date && $myTrade && $myTrade->start_date && $myTrade->end_date) {
            $tradeStart = \Carbon\Carbon::parse($theirTrade->start_date);
            $tradeEnd = \Carbon\Carbon::parse($theirTrade->end_date);
            $userStart = \Carbon\Carbon::parse($myTrade->start_date);
            $userEnd = \Carbon\Carbon::parse($myTrade->end_date);
            if ($tradeStart <= $userEnd && $userStart <= $tradeEnd) {
                $datesMatch = true;
                $overlapStart = max($tradeStart, $userStart);
                $overlapEnd = min($tradeEnd, $userEnd);
                $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;
                $datesDetail = $overlapDays . ' days overlap';
            } else {
                $gap = min(abs($tradeStart->diffInDays($userEnd)), abs($userStart->diffInDays($tradeEnd)));
                $datesDetail = $gap <= 7 ? 'Close dates (within 7 days)' : 'Different ranges';
            }
        } elseif ($theirTrade->start_date || ($myTrade && $myTrade->start_date)) {
            $datesMatch = true;
            $datesDetail = 'One is flexible';
        }
        $insights[] = [
            'label' => 'Date Range',
            'status' => $datesMatch ? 'good' : 'neutral',
            'their_value' => $theirDates,
            'your_value' => $myDates,
            'match_detail' => $datesDetail
        ];

        return $insights;
    }

    /**
     * Find which user trade matches the given trade
     * Returns the matching Trade object or null if no match
     */
    private function findMatchingUserTrade($trade, $user)
    {
        // Get ALL user's open trades (user can have multiple trades with different skills)
        $userOpenTrades = Trade::where('user_id', $user->id)
            ->where('status', 'open')
            ->get();

        if ($userOpenTrades->isEmpty()) {
            return null; // User must have an open trade to see matches
        }

        // A trade is compatible if it matches ANY of the user's open trades
        // For each trade, BOTH conditions must be true:
        // 1. The trade is looking for the skill the user is OFFERING in their posted trade
        // 2. The trade is offering the skill the user is LOOKING FOR in their posted trade

        foreach ($userOpenTrades as $userTrade) {
            // Check condition 1: Trade is looking for what the user is offering in this trade
            $tradeWantsWhatUserIsOffering = ($trade->looking_skill_id == $userTrade->offering_skill_id);

            // Check condition 2: Trade is offering what the user is looking for in this trade
            $tradeOffersWhatUserWants = ($trade->offering_skill_id == $userTrade->looking_skill_id);

            // If both conditions are true for this trade, return it as the match
            if ($tradeWantsWhatUserIsOffering && $tradeOffersWhatUserWants) {
                return $userTrade;
            }
        }

        // No match found across any of the user's trades
        return null;
    }

    /**
     * Check if a trade is compatible (kept for backward compatibility)
     */
    private function isTradeCompatible($trade, $user)
    {
        return $this->findMatchingUserTrade($trade, $user) !== null;
    }

    private function calculateCompatibility($trade, $matchingUserTrade = null)
    {
        $score = 0;
        $user = Auth::user();

        // Use the matching user trade if provided, otherwise get the first open trade
        if (!$matchingUserTrade) {
            $matchingUserTrade = Trade::where('user_id', $user->id)
                ->where('status', 'open')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
        }

        // If no matching trade, return base score
        if (!$matchingUserTrade) {
            return 0;
        }

        $tradeOwner = $trade->user;

        // Gender compatibility - check BOTH directions for symmetry
        $genderScore = 0;
        // Check: trade's preference vs user's gender
        if ($trade->gender_pref) {
            if ($trade->gender_pref === 'any' || ($user->gender && $trade->gender_pref === $user->gender)) {
                $genderScore += 10;
            }
        } else {
            $genderScore += 5; // No preference = neutral
        }
        // Check: user's trade preference vs trade owner's gender
        if ($matchingUserTrade->gender_pref) {
            if ($matchingUserTrade->gender_pref === 'any' || ($tradeOwner->gender && $matchingUserTrade->gender_pref === $tradeOwner->gender)) {
                $genderScore += 10;
            }
        } else {
            $genderScore += 5; // No preference = neutral
        }
        $score += $genderScore;

        // Location compatibility - symmetric comparison
        $locationScore = 0;
        $tradeLocation = $trade->location;
        $userTradeLocation = $matchingUserTrade->location;

        if ($tradeLocation && $userTradeLocation) {
            // Both have locations - compare them
            if (str_contains(strtolower($userTradeLocation), strtolower($tradeLocation)) ||
                str_contains(strtolower($tradeLocation), strtolower($userTradeLocation))) {
                $locationScore = 30; // High score for location match
            } else {
                $locationScore = 8; // Small partial credit for different locations
            }
        } elseif ($tradeLocation && $user->address) {
            // Trade has location, compare with user's profile address
            if (str_contains(strtolower($user->address), strtolower($tradeLocation)) ||
                str_contains(strtolower($tradeLocation), strtolower($user->address))) {
                $locationScore = 20; // Reduced weight match vs profile
            } else {
                $locationScore = 8;
            }
        } elseif ($userTradeLocation && $tradeOwner->address) {
            // User trade has location, compare with trade owner's profile address
            if (str_contains(strtolower($tradeOwner->address), strtolower($userTradeLocation)) ||
                str_contains(strtolower($userTradeLocation), strtolower($tradeOwner->address))) {
                $locationScore = 20; // Reduced weight match vs profile
            } else {
                $locationScore = 8;
            }
        } else {
            $locationScore = 10; // Neutral score if no location specified
        }
        $score += $locationScore;

        // Time availability overlap - symmetric calculation (always calculate, handles all cases)
        $score += $this->calculateTimeCompatibility($trade, $matchingUserTrade);

        // Days overlap - symmetric calculation (always calculate, handles all cases)
        $score += $this->calculateDayCompatibility($trade, $matchingUserTrade);

        // Date range compatibility - symmetric calculation (always calculate, handles all cases)
        $score += $this->calculateDateCompatibility($trade, $matchingUserTrade);

        return $score;
    }

    private function calculateTimeCompatibility($trade, $matchingUserTrade = null)
    {
        $score = 0;

        if (!$matchingUserTrade) {
            return 15; // Neutral score if no matching trade
        }

        $tradeHasTime = $trade->available_from && $trade->available_to;
        $userTradeHasTime = $matchingUserTrade->available_from && $matchingUserTrade->available_to;

        if ($tradeHasTime && $userTradeHasTime) {
            // Both have time preferences - calculate overlap
            $tradeStart = $this->timeToMinutes($trade->available_from);
            $tradeEnd = $this->timeToMinutes($trade->available_to);
            $userStart = $this->timeToMinutes($matchingUserTrade->available_from);
            $userEnd = $this->timeToMinutes($matchingUserTrade->available_to);

            // Check for overlap (including small gaps)
            $overlap = min($tradeEnd, $userEnd) - max($tradeStart, $userStart);

            if ($overlap > 0) {
                // Direct overlap - high score
                $score = 25;
            } else {
                // Check for small gaps (within 2 hours) - check both directions
                $gap1 = abs($tradeStart - $userEnd);
                $gap2 = abs($userStart - $tradeEnd);
                $gap = min($gap1, $gap2);

                if ($gap <= 120) { // 2 hours = 120 minutes
                    $score = 15; // Good score for small gap
                } elseif ($gap <= 240) { // 4 hours
                    $score = 10; // Moderate score for medium gap
                } else {
                    $score = 5; // Low score for large gap
                }
            }
        } elseif ($tradeHasTime || $userTradeHasTime) {
            // One has time preferences, the other is flexible - good match
            $score = 18;
        } else {
            // Neither has time preferences - both flexible
            $score = 15;
        }

        return $score;
    }

    private function calculateDayCompatibility($trade, $matchingUserTrade = null)
    {
        $score = 0;

        if (!$matchingUserTrade) {
            return 12; // Neutral score if no matching trade
        }

        $tradeDays = $trade->preferred_days && is_array($trade->preferred_days) ? $trade->preferred_days : [];
        $userDays = $matchingUserTrade->preferred_days && is_array($matchingUserTrade->preferred_days) ? $matchingUserTrade->preferred_days : [];

        // Case 1: Both have preferred days - calculate overlap
        if (!empty($tradeDays) && !empty($userDays)) {
            // Find overlapping days
            $overlappingDays = array_intersect($tradeDays, $userDays);
            $totalDays = count(array_unique(array_merge($tradeDays, $userDays)));

            if (count($overlappingDays) > 0) {
                // Calculate overlap percentage
                $overlapPercentage = (count($overlappingDays) / $totalDays) * 100;

                if ($overlapPercentage >= 50) {
                    $score += 20; // High score for good overlap
                } elseif ($overlapPercentage >= 25) {
                    $score += 15; // Medium score for moderate overlap
                } else {
                    $score += 10; // Low score for minimal overlap
                }
            } else {
                // No direct overlap, but check if one includes "everyday" or similar
                if (in_array('everyday', $tradeDays) || in_array('everyday', $userDays) ||
                    count($tradeDays) >= 5 || count($userDays) >= 5) {
                    $score += 12; // Good score for flexible schedules
                } else {
                    $score += 5; // Low score for no overlap
                }
            }
        }
        // Case 2: User has no days but other trade has days - user is flexible (good match)
        elseif (empty($userDays) && !empty($tradeDays)) {
            $score += 18; // Good score - user is flexible and can accommodate other's schedule
        }
        // Case 3: User has days but other trade has no days - other is flexible (good match)
        elseif (!empty($userDays) && empty($tradeDays)) {
            $score += 18; // Good score - other is flexible and can accommodate user's schedule
        }
        // Case 4: Neither has preferred days - both flexible (neutral)
        else {
            $score += 12; // Neutral score - both are flexible
        }

        return $score;
    }

    private function calculateDateCompatibility($trade, $matchingUserTrade = null)
    {
        $score = 0;

        if (!$matchingUserTrade) {
            return 10; // Neutral score if no matching trade
        }

        $tradeHasDates = $trade->start_date && $trade->end_date;
        $userTradeHasDates = $matchingUserTrade->start_date && $matchingUserTrade->end_date;

        if ($tradeHasDates && $userTradeHasDates) {
            // Both have date ranges - calculate overlap
            $tradeStart = \Carbon\Carbon::parse($trade->start_date);
            $tradeEnd = \Carbon\Carbon::parse($trade->end_date);
            $userStart = \Carbon\Carbon::parse($matchingUserTrade->start_date);
            $userEnd = \Carbon\Carbon::parse($matchingUserTrade->end_date);

            // Check for date overlap
            if ($tradeStart <= $userEnd && $userStart <= $tradeEnd) {
                // Direct overlap
                $overlapStart = max($tradeStart, $userStart);
                $overlapEnd = min($tradeEnd, $userEnd);
                $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;

                if ($overlapDays >= 7) {
                    $score = 15; // High score for week+ overlap
                } elseif ($overlapDays >= 3) {
                    $score = 12; // Medium score for 3+ days overlap
                } else {
                    $score = 8; // Low score for minimal overlap
                }
            } else {
                // Check for small gaps (within 7 days) - check both directions
                $gap1 = abs($tradeStart->diffInDays($userEnd));
                $gap2 = abs($userStart->diffInDays($tradeEnd));
                $gap = min($gap1, $gap2);

                if ($gap <= 7) {
                    $score = 10; // Good score for small gap
                } elseif ($gap <= 14) {
                    $score = 7; // Moderate score for medium gap
                } else {
                    $score = 3; // Low score for large gap
                }
            }
        } elseif ($tradeHasDates || $userTradeHasDates) {
            // One has date preferences, the other is flexible - good match
            $score = 12;
        } else {
            // Neither has date preferences - both flexible
            $score = 10;
        }

        return $score;
    }

    private function timeToMinutes($time)
    {
        $parts = explode(':', $time);
        return ($parts[0] * 60) + $parts[1];
    }

    public function requests()
    {
        $user = Auth::user();

        // Get incoming requests (only pending ones for actions)
        $incoming = TradeRequest::with(['trade', 'requester'])
            ->whereHas('trade', function($q) use ($user){
                $q->where('user_id',$user->id);
            })
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(function($req) use ($user) {
                if ($req->trade) {
                    // For incoming requests, evaluate from the REQUESTER'S perspective
                    $matchingRequesterTrade = $this->findMatchingUserTrade($req->trade, $req->requester);
                    $req->trade->is_compatible = ($matchingRequesterTrade !== null);
                    $req->trade->compatibility_score = $this->calculateCompatibility($req->trade, $matchingRequesterTrade);
                    // Build insights from the requester's perspective for skills and from viewer for timing overlap (uses viewer's open trade)
                    $req->trade->insights = $this->buildMatchInsights($req->trade, $req->requester);

                    // Attach explicit trade contexts for the view labels
                    $req->viewer_trade = $req->trade->loadMissing(['offeringSkill','lookingSkill']);
                    $req->other_trade = $this->getUserOpenTrade($req->requester);
                    if ($req->other_trade) {
                        $req->other_trade->load(['offeringSkill','lookingSkill']);
                    }
                }
                return $req;
            });

        // Get outgoing requests (pending and declined for user to see)
        $outgoing = TradeRequest::with(['trade', 'trade.user'])
            ->where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'declined'])
            ->latest()
            ->get()
            ->map(function($req) use ($user) {
                if ($req->trade) {
                    // For outgoing requests, evaluate from the current user's perspective
                    $matchingUserTrade = $this->findMatchingUserTrade($req->trade, $user);
                    $req->trade->is_compatible = ($matchingUserTrade !== null);
                    $req->trade->compatibility_score = $this->calculateCompatibility($req->trade, $matchingUserTrade);
                    $req->trade->insights = $this->buildMatchInsights($req->trade, $user);

                    // Attach explicit trade contexts for the view labels
                    $req->viewer_trade = $this->getUserOpenTrade($user);
                    if ($req->viewer_trade) {
                        $req->viewer_trade->load(['offeringSkill','lookingSkill']);
                    }
                    $req->other_trade = $req->trade->loadMissing(['offeringSkill','lookingSkill']);
                }
                return $req;
            });

        return view('trades.requests', compact('incoming','outgoing'));
    }

    public function ongoing()
    {
        $user = Auth::user();

        // Get trades where user is the owner and status is ongoing
        $ownedOngoing = Trade::with(['user', 'offeringSkill', 'lookingSkill'])
            ->where('user_id', $user->id)
            ->where('status', 'ongoing')
            ->get();

        // Get trades where user is the requester and their request was accepted
        $requestedOngoing = Trade::with(['user', 'offeringSkill', 'lookingSkill'])
            ->whereHas('requests', function($query) use ($user) {
                $query->where('requester_id', $user->id)
                      ->where('status', 'accepted');
            })
            ->where('status', 'ongoing')
            ->get();

        // Combine both collections
        $ongoing = $ownedOngoing->merge($requestedOngoing);



        return view('trades.ongoing', compact('ongoing', 'ownedOngoing', 'requestedOngoing'));
    }

    public function notify()
    {
        $user = Auth::user();

        // Mark all notifications as read when visiting the page
        \DB::table('user_notifications')
            ->where('user_id', $user->id)
            ->where('read', false)
            ->update(['read' => true]);

        $notifications = \DB::table('user_notifications')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function($notification) {
                $notification->data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
                return $notification;
            });

        // Quick-card: unread announcements count for this user
        $announcementsCount = \App\Models\Announcement::active()
            ->audienceForUser($user)
            ->get()
            ->reject(function($a) use ($user) { return $a->isReadBy($user); })
            ->count();

        return view('trades.tradenotifications', compact('notifications', 'announcementsCount'));
    }

    public function announcements()
    {
        $user = Auth::user();

        // Get all active announcements for this user
        $announcements = \App\Models\Announcement::active()
            ->audienceForUser($user)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('announcements.index', compact('announcements'));
    }

    // Simple manage view listing user's own posted trades
    public function manage()
    {
        $user = Auth::user();
        $trades = Trade::with(['offeringSkill', 'lookingSkill'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);
        return view('trades.manage', compact('trades'));
    }

    public function edit(Trade $trade)
    {
        $user = Auth::user();
        if ($trade->user_id !== $user->id) {
            return redirect()->route('trades.manage')->with('error', 'Unauthorized');
        }

        // Get all skills for "looking for" dropdown
        $skills = Skill::orderBy('category')->orderBy('name')->get();

        // Get user's registered skills (from user_skills table)
        $registeredSkills = $user->skills()->orderBy('category')->orderBy('name')->get();

        // Get user's acquired skills (from skill_acquisition_history)
        $acquiredSkills = $user->getAcquiredSkills();

        // Merge registered and acquired skills, remove duplicates, and sort
        $userAllSkills = $registeredSkills
            ->merge($acquiredSkills)
            ->unique('skill_id')
            ->sortBy(function($skill) {
                return $skill->category . '|' . $skill->name;
            })
            ->values();

        // Check if trade has active requests (pending or accepted) OR if trade is ongoing
        // Ongoing trades have accepted requests and skills should not be changed
        $hasActiveRequests = $trade->status === 'ongoing' ||
            $trade->requests()
                ->whereIn('status', ['pending', 'accepted'])
                ->exists();

        // Get request count for display
        $activeRequestCount = 0;
        if ($hasActiveRequests) {
            if ($trade->status === 'ongoing') {
                $activeRequestCount = $trade->requests()->where('status', 'accepted')->count();
            } else {
                $activeRequestCount = $trade->requests()->whereIn('status', ['pending', 'accepted'])->count();
            }
        }

        return view('trades.edit', compact('trade', 'skills', 'userAllSkills', 'hasActiveRequests', 'activeRequestCount'));
    }

    public function update(Request $request, Trade $trade)
    {
        $user = Auth::user();
        if ($trade->user_id !== $user->id) {
            return redirect()->route('trades.manage')->with('error', 'Unauthorized');
        }

        // Check if trade has active requests OR is ongoing - if so, prevent skill changes
        // Ongoing trades have active sessions and skills should not be changed
        $hasActiveRequests = $trade->status === 'ongoing' ||
            $trade->requests()
                ->whereIn('status', ['pending', 'accepted'])
                ->exists();

        if ($hasActiveRequests) {
            // Check if user is trying to change skills
            $offeringChanged = $request->offering_skill_id != $trade->offering_skill_id;
            $lookingChanged = $request->looking_skill_id != $trade->looking_skill_id;

            if ($offeringChanged || $lookingChanged) {
                $message = $trade->status === 'ongoing'
                    ? 'Cannot change skills while the trade session is ongoing. The session must be completed or closed first.'
                    : 'Cannot change skills while there are active requests on this trade. Please respond to or cancel all requests first.';

                return redirect()->back()
                    ->withErrors([
                        'offering_skill_id' => $offeringChanged ? ($trade->status === 'ongoing' ? 'Cannot change offering skill while session is ongoing.' : 'Cannot change offering skill while there are active requests on this trade.') : null,
                        'looking_skill_id' => $lookingChanged ? ($trade->status === 'ongoing' ? 'Cannot change looking-for skill while session is ongoing.' : 'Cannot change looking-for skill while there are active requests on this trade.') : null,
                    ])
                    ->with('warning', $message)
                    ->withInput();
            }
        }

        $validated = $request->validate([
            'offering_skill_id' => ['required', 'exists:skills,skill_id'],
            'looking_skill_id' => ['required', 'exists:skills,skill_id', 'different:offering_skill_id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            // Accept flexible time formats like 06:31 pm, 18:31, etc.
            'available_from' => ['nullable', 'string'],
            'available_to' => ['nullable', 'string'],
            'preferred_days' => ['nullable', 'array'],
            'preferred_days.*' => ['in:Mon,Tue,Wed,Thu,Fri,Sat,Sun'],
            'gender_pref' => ['nullable', 'in:any,male,female'],
            'location' => ['nullable', 'string', 'max:255'],
            'session_type' => ['required', 'in:any,online,onsite'],
            'use_username' => ['nullable', 'boolean'],
        ]);

        // Validate that the offering skill is either registered or acquired
        $hasRegistered = $user->skills()->where('user_skills.skill_id', $validated['offering_skill_id'])->exists();
        $acquiredSkills = $user->getAcquiredSkills();
        $hasAcquired = $acquiredSkills->pluck('skill_id')->contains($validated['offering_skill_id']);

        if (!$hasRegistered && !$hasAcquired) {
            return redirect()->back()
                ->withErrors(['offering_skill_id' => 'You can only offer skills that you have registered or acquired in your profile.'])
                ->withInput();
        }

        // Normalize checkbox/array inputs
        $validated['use_username'] = $request->boolean('use_username');
        $validated['preferred_days'] = $validated['preferred_days'] ?? [];

        // Normalize time strings to H:i (24-hour) if provided
        foreach (['available_from','available_to'] as $timeField) {
            if (!empty($validated[$timeField])) {
                try {
                    $validated[$timeField] = \Carbon\Carbon::parse($validated[$timeField])->format('H:i');
                } catch (\Throwable $e) {
                    // If parsing fails, set null rather than rejecting the whole update
                    $validated[$timeField] = null;
                }
            }
        }

        \Log::info('Trade update request', [
            'trade_id' => $trade->id,
            'user_id' => $user->id,
            'payload' => $validated,
        ]);

        $trade->update($validated);

        \Log::info('Trade updated row', $trade->fresh()->only([
            'id','use_username','looking_skill_id','start_date','end_date','available_from','available_to','preferred_days','gender_pref','location','session_type'
        ]));

        return redirect()->route('trades.manage')->with('success', 'Trade updated');
    }

    public function destroy(Trade $trade)
    {
        $user = Auth::user();
        if ($trade->user_id !== $user->id) {
            return redirect()->route('trades.manage')->with('error', 'Unauthorized');
        }
        // Only allow deleting open trades
        if ($trade->status !== 'open') {
            return redirect()->route('trades.manage')->with('error', 'Only open trades can be deleted');
        }
        $trade->delete();
        return redirect()->route('trades.manage')->with('success', 'Trade deleted');
    }

    public static function getUnreadNotificationCount($userId)
    {
        return \DB::table('user_notifications')
            ->where('user_id', $userId)
            ->where('read', false)
            ->count();
    }

    public function requestTrade(Request $request, Trade $trade)
    {
        $user = Auth::user();

        // Check if user already has a pending request for this trade
        $existingRequest = TradeRequest::where('trade_id', $trade->id)
            ->where('requester_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return redirect()->back()->with('error', 'You already have a pending request for this trade.');
        }

        // Check if user has sufficient tokens for future acceptance (but don't charge yet)
        // Premium users have unlimited requests - skip token check
        $requestFee = TradeFeeSetting::getFeeAmount('trade_request');
        $isPremium = $user->isPremium(); // Use isPremium() method which checks plan and expiration date

        if (!$isPremium && $requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
            if ($user->token_balance < $requestFee) {
                return redirect()->back()->with('error', "Insufficient tokens. You need {$requestFee} tokens to request this trade. Current balance: {$user->token_balance} tokens.");
            }
        }

        // Create trade request
        TradeRequest::create([
            'trade_id' => $trade->id,
            'requester_id' => $user->id,
            'status' => 'pending',
            'message' => $request->message ?? '',
        ]);

        // Create notification for trade owner
        \DB::table('user_notifications')->insert([
            'user_id' => $trade->user_id,
            'type' => 'trade_request',
            'data' => json_encode([
                'requester_name' => ($user->use_username ? $user->username : ($user->firstname . ' ' . $user->lastname)),
                'requester_username' => $user->username,
                'trade_id' => $trade->id,
                'offering_skill' => $trade->offeringSkill->name ?? 'Unknown Skill',
                'looking_skill' => $trade->lookingSkill->name ?? 'Unknown Skill',
                'message' => $request->message ?? '',
            ]),
            'read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $successMessage = 'Trade request sent successfully!';
        if ($isPremium) {
            $successMessage .= " As a Premium member, no token fees will be charged.";
        } elseif ($requestFee > 0) {
            $successMessage .= " {$requestFee} token fee will be charged when the trade is accepted.";
        }

        return redirect()->back()->with('success', $successMessage);
    }

    /**
     * Cancel the current user's pending request for a trade.
     */
    public function cancelRequest(Request $request, Trade $trade)
    {
        $user = Auth::user();

        // Find pending request by this user for the trade
        $pending = TradeRequest::where('trade_id', $trade->id)
            ->where('requester_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$pending) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No pending request found to cancel.'], 404);
            }
            return redirect()->back()->with('error', 'No pending request found to cancel.');
        }

        // Mark as cancelled (do not delete for audit trail)
        $pending->update([
            'status' => 'cancelled',
            'responded_at' => now(),
        ]);

        // Notify trade owner that the request was cancelled (optional, silent for now)
        try {
            \DB::table('user_notifications')->insert([
                'user_id' => $trade->user_id,
                'type' => 'trade_request_cancelled',
                'data' => json_encode([
                    'requester_username' => $user->username,
                    'trade_id' => $trade->id,
                ]),
                'read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Non-fatal
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Trade request cancelled.');
    }

    public function respondToRequest(Request $request, TradeRequest $tradeRequest)
    {
        $user = Auth::user();

        // Verify the user owns the trade
        if ($tradeRequest->trade->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $action = $request->input('action');
        if (!in_array($action, ['accept', 'decline'])) {
            return redirect()->back()->with('error', 'Invalid action.');
        }

        // Convert action to status
        $status = ($action === 'accept') ? 'accepted' : 'declined';
        $acceptanceFee = 0; // Initialize for scope

        // Update trade request
        $tradeRequest->update([
            'status' => $status,
            'responded_at' => now(),
        ]);

        // Create notification for requester
        \DB::table('user_notifications')->insert([
            'user_id' => $tradeRequest->requester_id,
            'type' => 'trade_response',
            'data' => json_encode([
                'trade_owner_name' => ($user->use_username ? $user->username : ($user->firstname . ' ' . $user->lastname)),
                'trade_owner_username' => $user->username,
                'trade_id' => $tradeRequest->trade_id,
                'offering_skill' => $tradeRequest->trade->offeringSkill->name ?? 'Unknown Skill',
                'looking_skill' => $tradeRequest->trade->lookingSkill->name ?? 'Unknown Skill',
                'status' => $status,
            ]),
            'read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // If accepted, charge token fees to both users and update trade status to ongoing
        if ($status === 'accepted') {
            $requester = $tradeRequest->requester;
            $accepter = $user;

            // Check if users are premium (premium users have unlimited requests/acceptances)
            $requesterIsPremium = $requester->isPremium(); // Use isPremium() method which checks plan and expiration date
            $accepterIsPremium = $accepter->isPremium(); // Use isPremium() method which checks plan and expiration date

            // Get fee amounts
            $requestFee = TradeFeeSetting::getFeeAmount('trade_request');
            $acceptanceFee = TradeFeeSetting::getFeeAmount('trade_acceptance');

            // Check if both users have sufficient tokens (skip for premium users)
            $totalFeeRequired = 0;
            if (!$requesterIsPremium && $requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
                $totalFeeRequired += $requestFee;
            }
            if (!$accepterIsPremium && $acceptanceFee > 0 && TradeFeeSetting::isFeeActive('trade_acceptance')) {
                $totalFeeRequired += $acceptanceFee;
            }

            // Check requester's balance (skip for premium users)
            if (!$requesterIsPremium && $requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
                if ($requester->token_balance < $requestFee) {
                    // Revert the request status if insufficient tokens
                    $tradeRequest->update(['status' => 'pending', 'responded_at' => null]);
                    return redirect()->back()->with('error', "Cannot accept trade. The requester ({$requester->name}) has insufficient tokens. They need {$requestFee} tokens but have {$requester->token_balance} tokens.");
                }
            }

            // Check accepter's balance (skip for premium users)
            if (!$accepterIsPremium && $acceptanceFee > 0 && TradeFeeSetting::isFeeActive('trade_acceptance')) {
                if ($accepter->token_balance < $acceptanceFee) {
                    // Revert the request status if insufficient tokens
                    $tradeRequest->update(['status' => 'pending', 'responded_at' => null]);
                    return redirect()->back()->with('error', "Insufficient tokens to accept trade. You need {$acceptanceFee} tokens. Current balance: {$accepter->token_balance} tokens.");
                }
            }

            // Charge fees to both users (skip for premium users)
            if (!$requesterIsPremium && $requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
                // Charge requester
                $requester->token_balance -= $requestFee;
                $requester->save();

                // Record fee transaction for requester
                FeeTransaction::create([
                    'user_id' => $requester->id,
                    'fee_type' => 'trade_request_fee',
                    'amount' => -$requestFee, // Negative amount for deduction
                    'trade_id' => $tradeRequest->trade_id,
                    'description' => "Trade request fee for accepted trade #{$tradeRequest->trade_id}",
                    'status' => 'completed',
                ]);
            }

            if (!$accepterIsPremium && $acceptanceFee > 0 && TradeFeeSetting::isFeeActive('trade_acceptance')) {
                // Charge accepter
                $accepter->token_balance -= $acceptanceFee;
                $accepter->save();

                // Record fee transaction for accepter
                FeeTransaction::create([
                    'user_id' => $accepter->id,
                    'fee_type' => 'trade_acceptance_fee',
                    'amount' => -$acceptanceFee, // Negative amount for deduction
                    'trade_id' => $tradeRequest->trade_id,
                    'description' => "Trade acceptance fee for trade #{$tradeRequest->trade_id}",
                    'status' => 'completed',
                ]);
            }

            $tradeRequest->trade->update(['status' => 'ongoing']);
        }

        $statusText = $status === 'accepted' ? 'accepted' : 'declined';
        $successMessage = "Trade request {$statusText} successfully!";

        if ($status === 'accepted') {
            $requester = $tradeRequest->requester;
            $accepter = $user;
            $requesterIsPremium = $requester->isPremium(); // Use isPremium() method which checks plan and expiration date
            $accepterIsPremium = $accepter->isPremium(); // Use isPremium() method which checks plan and expiration date

            $totalFeesCharged = 0;
            $premiumUsers = [];

            if (!$requesterIsPremium && $requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
                $totalFeesCharged += $requestFee;
            } else if ($requesterIsPremium) {
                $premiumUsers[] = $requester->name;
            }

            if (!$accepterIsPremium && $acceptanceFee > 0 && TradeFeeSetting::isFeeActive('trade_acceptance')) {
                $totalFeesCharged += $acceptanceFee;
            } else if ($accepterIsPremium) {
                $premiumUsers[] = $accepter->name;
            }

            if ($totalFeesCharged > 0) {
                $successMessage .= " Token fees charged: {$totalFeesCharged} tokens total.";
            }

            if (!empty($premiumUsers)) {
                $premiumMessage = count($premiumUsers) === 2
                    ? "Both users are Premium members - no token fees charged."
                    : implode(' and ', $premiumUsers) . " is a Premium member - no token fees charged for them.";
                $successMessage .= " " . $premiumMessage;
            }
        }

        return redirect()->back()->with('success', $successMessage);
    }

    public function markNotificationAsRead($id)
    {
        $user = Auth::user();

        // Mark notification as read
        \DB::table('user_notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update(['read' => true]);

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    /**
     * Display user's trade history (completed/closed trades)
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        // Get all trades where user is involved (owner or participant)
        $query = Trade::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('requests', function($subQuery) use ($user) {
                      $subQuery->where('requester_id', $user->id)
                               ->where('status', 'accepted');
                  });
            })
            ->where('status', 'closed')
            ->with(['offeringSkill', 'lookingSkill', 'user', 'requests' => function($q) {
                $q->where('status', 'accepted')->with('requester');
            }]);

        // Optional date filters
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $quick = $request->get('range'); // e.g., 1w, 3m

        // Apply quick range if provided (only if custom dates are not set)
        // Custom dates take priority over quick filters
        if ($quick && !$fromDate && !$toDate) {
            switch ($quick) {
                case '1w':
                    $fromDate = now()->subWeek()->toDateString();
                    break;
                case '3m':
                    $fromDate = now()->subMonths(3)->toDateString();
                    break;
            }
            $toDate = now()->toDateString(); // Always set toDate to today for quick filters
        }

        // Apply date filters
        if ($fromDate) {
            $query->whereDate('updated_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('updated_at', '<=', $toDate);
        }

        // Paginate with query string preservation
        $trades = $query->orderBy('updated_at', 'desc')->paginate(15)->withQueryString();

        // Calculate statistics
        $totalCompleted = Trade::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('requests', function($subQuery) use ($user) {
                      $subQuery->where('requester_id', $user->id)
                               ->where('status', 'accepted');
                  });
            })
            ->where('status', 'closed')
            ->count();

        $thisMonth = Trade::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('requests', function($subQuery) use ($user) {
                      $subQuery->where('requester_id', $user->id)
                               ->where('status', 'accepted');
                  });
            })
            ->where('status', 'closed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $thisYear = Trade::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('requests', function($subQuery) use ($user) {
                      $subQuery->where('requester_id', $user->id)
                               ->where('status', 'accepted');
                  });
            })
            ->where('status', 'closed')
            ->whereYear('updated_at', now()->year)
            ->count();

        $stats = [
            'total_completed' => $totalCompleted,
            'this_month' => $thisMonth,
            'this_year' => $thisYear,
        ];

        return view('trades.history', compact('trades', 'stats'));
    }
}
