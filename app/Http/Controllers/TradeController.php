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

        $skills = Skill::orderBy('category')->orderBy('name')->get();
        return view('trades.create', compact('user', 'skills'));
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

        $validated = $request->validate([
            'offering_skill_id' => ['required', 'exists:skills,skill_id'],
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
            return view('trades.matches', ['trades' => collect([]), 'noSkill' => true]);
        }

        // Require user has posted at least one open trade before viewing matches
        $userOpenTrade = Trade::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();
        if (!$userOpenTrade) {
            return view('trades.matches', [
                'trades' => collect([]),
                'noTradePosted' => true
            ]);
        }

        // Get all open trades from other users
        $trades = Trade::with(['user', 'offeringSkill', 'lookingSkill'])
            ->where('user_id', '!=', $user->id)
            ->where('status', 'open')
            ->get()
            ->map(function($trade) use ($user) {
                // Calculate compatibility score
                $trade->compatibility_score = $this->calculateCompatibility($trade);

                // Check if this trade is compatible with user's skill
                $trade->is_compatible = $this->isTradeCompatible($trade, $user);

                // Build human-readable insights (Venn diagram style)
                $trade->insights = $this->buildMatchInsights($trade, $user);

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

        return view('trades.matches', compact('trades'));
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
     * Build Venn-style insights for why this trade matches the viewer
     */
    private function buildMatchInsights($theirTrade, $user)
    {
        $insights = [];
        $myTrade = $this->getUserOpenTrade($user);

        // Skill relation
        $skillOk = ($theirTrade->looking_skill_id == ($user->skill_id ?? null))
            || ($myTrade && $myTrade->looking_skill_id == $theirTrade->offering_skill_id);
        $insights[] = [
            'label' => 'Skill',
            'status' => $skillOk ? 'good' : 'neutral',
            'detail' => ($theirTrade->offeringSkill->name ?? 'Offering') . ' ↔ ' . ($theirTrade->lookingSkill->name ?? 'Looking')
        ];

        // Gender preference (their pref vs your profile)
        $genderOk = ($theirTrade->gender_pref === 'any' || !$theirTrade->gender_pref || !$user->gender || $theirTrade->gender_pref === $user->gender);
        $insights[] = [
            'label' => 'Gender',
            'status' => $genderOk ? 'good' : 'bad',
            'detail' => $theirTrade->gender_pref ? ('Pref: ' . strtoupper($theirTrade->gender_pref)) : 'No pref'
        ];

        // Session type
        $sessionOk = !$myTrade || ($theirTrade->session_type === 'any' || $myTrade->session_type === 'any' || $theirTrade->session_type === $myTrade->session_type);
        $insights[] = [
            'label' => 'Session',
            'status' => $sessionOk ? 'good' : 'neutral',
            'detail' => strtoupper($theirTrade->session_type)
        ];

        // Location
        $locationOk = false; $locationDetail = 'Flexible';
        if ($theirTrade->location) {
            if ($myTrade && $myTrade->location) {
                $locationOk = str_contains(strtolower($myTrade->location), strtolower($theirTrade->location))
                    || str_contains(strtolower($theirTrade->location), strtolower($myTrade->location));
                $locationDetail = $theirTrade->location;
            } elseif ($user->address) {
                $locationOk = str_contains(strtolower($user->address), strtolower($theirTrade->location))
                    || str_contains(strtolower($theirTrade->location), strtolower($user->address));
                $locationDetail = $theirTrade->location;
            }
        }
        $insights[] = [
            'label' => 'Location',
            'status' => $locationOk ? 'good' : 'neutral',
            'detail' => $locationDetail
        ];

        // Time overlap
        $timeDetail = 'No prefs'; $timeStatus = 'neutral';
        if ($theirTrade->available_from && $theirTrade->available_to) {
            if ($myTrade && $myTrade->available_from && $myTrade->available_to) {
                $tradeStart = $this->timeToMinutes($theirTrade->available_from);
                $tradeEnd = $this->timeToMinutes($theirTrade->available_to);
                $userStart = $this->timeToMinutes($myTrade->available_from);
                $userEnd = $this->timeToMinutes($myTrade->available_to);
                $overlap = min($tradeEnd, $userEnd) - max($tradeStart, $userStart);
                if ($overlap > 0) { $timeStatus = 'good'; $timeDetail = floor($overlap) . ' min overlap'; }
                else { $timeStatus = 'neutral'; $timeDetail = 'Different times'; }
            } else {
                $timeDetail = 'They set a time';
            }
        }
        $insights[] = [ 'label' => 'Time', 'status' => $timeStatus, 'detail' => $timeDetail ];

        // Day overlap
        $dayDetail = 'No prefs'; $dayStatus = 'neutral';
        if ($theirTrade->preferred_days && is_array($theirTrade->preferred_days)) {
            if ($myTrade && is_array($myTrade->preferred_days)) {
                $overlapDays = array_values(array_intersect($theirTrade->preferred_days, $myTrade->preferred_days));
                if (count($overlapDays) > 0) { $dayStatus = 'good'; $dayDetail = implode(', ', $overlapDays); }
                else { $dayDetail = 'Different days'; }
            } else { $dayDetail = implode(', ', $theirTrade->preferred_days); }
        }
        $insights[] = [ 'label' => 'Days', 'status' => $dayStatus, 'detail' => $dayDetail ];

        // Date overlap
        $dateDetail = 'No prefs'; $dateStatus = 'neutral';
        if ($theirTrade->start_date && $theirTrade->end_date) {
            if ($myTrade && $myTrade->start_date && $myTrade->end_date) {
                $tradeStart = \Carbon\Carbon::parse($theirTrade->start_date);
                $tradeEnd = \Carbon\Carbon::parse($theirTrade->end_date);
                $userStart = \Carbon\Carbon::parse($myTrade->start_date);
                $userEnd = \Carbon\Carbon::parse($myTrade->end_date);
                if ($tradeStart <= $userEnd && $userStart <= $tradeEnd) {
                    $overlapStart = max($tradeStart, $userStart);
                    $overlapEnd = min($tradeEnd, $userEnd);
                    $dateStatus = 'good';
                    $dateDetail = $overlapStart->diffInDays($overlapEnd) + 1 . ' day overlap';
                } else { $dateDetail = 'Different ranges'; }
            } else { $dateDetail = $theirTrade->start_date . ' - ' . $theirTrade->end_date; }
        }
        $insights[] = [ 'label' => 'Dates', 'status' => $dateStatus, 'detail' => $dateDetail ];

        return $insights;
    }

    private function isTradeCompatible($trade, $user)
    {
        // Check if user's skill matches what the trade is looking for
        if ($trade->looking_skill_id == $user->skill_id) {
            return true;
        }

        // Check if user has a trade and their looking skill matches what this trade is offering
        $userTrade = Trade::where('user_id', $user->id)->where('status', 'open')->first();
        if ($userTrade && $userTrade->looking_skill_id == $trade->offering_skill_id) {
            return true;
        }

        return false;
    }

    private function calculateCompatibility($trade)
    {
        $score = 0;
        $user = Auth::user();

        // Gender compatibility (their preference vs your profile gender)
        if ($trade->gender_pref && $user->gender) {
            if ($trade->gender_pref === 'any' || $trade->gender_pref === $user->gender) {
                $score += 20; // High score for gender match
            } else {
                $score += 0; // No score for gender mismatch
            }
        } else {
            $score += 10; // Neutral score if no gender preference specified
        }

        // Location compatibility (prefer comparing trade->location vs YOUR OPEN TRADE's location).
        $userTradeForLocation = Trade::where('user_id', $user->id)
            ->where('status', 'open')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($trade->location && $userTradeForLocation && $userTradeForLocation->location) {
            if (str_contains(strtolower($userTradeForLocation->location), strtolower($trade->location)) ||
                str_contains(strtolower($trade->location), strtolower($userTradeForLocation->location))) {
                $score += 30; // High score for location match
            } else {
                $score += 8; // Small partial credit for different locations
            }
        } else {
            // Fallback: compare against user's profile address, otherwise neutral
            if ($trade->location && $user->address) {
                if (str_contains(strtolower($user->address), strtolower($trade->location)) ||
                    str_contains(strtolower($trade->location), strtolower($user->address))) {
                    $score += 20; // Reduced weight match vs profile
                } else {
                    $score += 8;
                }
            } else {
                $score += 10; // Neutral score if no location specified
            }
        }

        // Time availability overlap with flexible matching
        if ($trade->available_from && $trade->available_to) {
            $score += $this->calculateTimeCompatibility($trade);
        }

        // Days overlap with flexible matching
        if ($trade->preferred_days && is_array($trade->preferred_days)) {
            $score += $this->calculateDayCompatibility($trade);
        }

        // Date range compatibility
        if ($trade->start_date && $trade->end_date) {
            $score += $this->calculateDateCompatibility($trade);
        }

        return $score;
    }

    private function calculateTimeCompatibility($trade)
    {
        $score = 0;
        $user = Auth::user();

        // If user has an OPEN trade with time preferences, check for overlap (latest open post)
        $userTrade = Trade::where('user_id', $user->id)
            ->where('status', 'open')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($userTrade && $userTrade->available_from && $userTrade->available_to) {
            // Convert times to minutes for easier comparison
            $tradeStart = $this->timeToMinutes($trade->available_from);
            $tradeEnd = $this->timeToMinutes($trade->available_to);
            $userStart = $this->timeToMinutes($userTrade->available_from);
            $userEnd = $this->timeToMinutes($userTrade->available_to);

            // Check for overlap (including small gaps)
            $overlap = min($tradeEnd, $userEnd) - max($tradeStart, $userStart);

            if ($overlap > 0) {
                // Direct overlap - high score
                $score += 25;
            } else {
                // Check for small gaps (within 2 hours)
                $gap = abs($tradeStart - $userEnd);
                if ($gap <= 120) { // 2 hours = 120 minutes
                    $score += 15; // Good score for small gap
                } elseif ($gap <= 240) { // 4 hours
                    $score += 10; // Moderate score for medium gap
                } else {
                    $score += 5; // Low score for large gap
                }
            }
        } else {
            // User doesn't have time preferences, give moderate score
            $score += 15;
        }

        return $score;
    }

    private function calculateDayCompatibility($trade)
    {
        $score = 0;
        $user = Auth::user();

        // If user has an OPEN trade with day preferences, check for overlap (latest open post)
        $userTrade = Trade::where('user_id', $user->id)
            ->where('status', 'open')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($userTrade && $userTrade->preferred_days && is_array($userTrade->preferred_days)) {
            $tradeDays = $trade->preferred_days;
            $userDays = $userTrade->preferred_days;

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
        } else {
            // User doesn't have day preferences, give moderate score
            $score += 12;
        }

        return $score;
    }

    private function calculateDateCompatibility($trade)
    {
        $score = 0;
        $user = Auth::user();

        // If user has an OPEN trade with date preferences, check for overlap (latest open post)
        $userTrade = Trade::where('user_id', $user->id)
            ->where('status', 'open')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($userTrade && $userTrade->start_date && $userTrade->end_date) {
            $tradeStart = \Carbon\Carbon::parse($trade->start_date);
            $tradeEnd = \Carbon\Carbon::parse($trade->end_date);
            $userStart = \Carbon\Carbon::parse($userTrade->start_date);
            $userEnd = \Carbon\Carbon::parse($userTrade->end_date);

            // Check for date overlap
            if ($tradeStart <= $userEnd && $userStart <= $tradeEnd) {
                // Direct overlap
                $overlapStart = max($tradeStart, $userStart);
                $overlapEnd = min($tradeEnd, $userEnd);
                $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;

                if ($overlapDays >= 7) {
                    $score += 15; // High score for week+ overlap
                } elseif ($overlapDays >= 3) {
                    $score += 12; // Medium score for 3+ days overlap
                } else {
                    $score += 8; // Low score for minimal overlap
                }
            } else {
                // Check for small gaps (within 7 days)
                $gap = min(
                    abs($tradeStart->diffInDays($userEnd)),
                    abs($userStart->diffInDays($tradeEnd))
                );

                if ($gap <= 7) {
                    $score += 8; // Good score for small gap
                } elseif ($gap <= 14) {
                    $score += 5; // Moderate score for medium gap
                } else {
                    $score += 2; // Low score for large gap
                }
            }
        } else {
            // User doesn't have date preferences, give moderate score
            $score += 8;
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
                    $req->trade->compatibility_score = $this->calculateCompatibility($req->trade);
                    // For incoming requests, evaluate skill gate from the REQUESTER'S perspective
                    // so it reflects why they could find you in Matches and decided to request.
                    $req->trade->is_compatible = $this->isTradeCompatible($req->trade, $req->requester);
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
                    $req->trade->compatibility_score = $this->calculateCompatibility($req->trade);
                    // Use SKILL GATE for compatibility flag
                    $req->trade->is_compatible = $this->isTradeCompatible($req->trade, $user);
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
        $skills = Skill::orderBy('category')->orderBy('name')->get();
        return view('trades.edit', compact('trade', 'skills'));
    }

    public function update(Request $request, Trade $trade)
    {
        $user = Auth::user();
        if ($trade->user_id !== $user->id) {
            return redirect()->route('trades.manage')->with('error', 'Unauthorized');
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

        // Always enforce offering skill to be the user's registered skill (same as create UI)
        if ($user->skill) {
            $validated['offering_skill_id'] = $user->skill->skill_id;
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
        $requestFee = TradeFeeSetting::getFeeAmount('trade_request');
        if ($requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
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
        if ($requestFee > 0) {
            $successMessage .= " {$requestFee} token fee will be charged when the trade is accepted.";
        }

        return redirect()->back()->with('success', $successMessage);
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

            // Get fee amounts
            $requestFee = TradeFeeSetting::getFeeAmount('trade_request');
            $acceptanceFee = TradeFeeSetting::getFeeAmount('trade_acceptance');

            // Check if both users have sufficient tokens
            $totalFeeRequired = 0;
            if ($requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
                $totalFeeRequired += $requestFee;
            }
            if ($acceptanceFee > 0 && TradeFeeSetting::isFeeActive('trade_acceptance')) {
                $totalFeeRequired += $acceptanceFee;
            }

            // Check requester's balance
            if ($requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
                if ($requester->token_balance < $requestFee) {
                    // Revert the request status if insufficient tokens
                    $tradeRequest->update(['status' => 'pending', 'responded_at' => null]);
                    return redirect()->back()->with('error', "Cannot accept trade. The requester ({$requester->name}) has insufficient tokens. They need {$requestFee} tokens but have {$requester->token_balance} tokens.");
                }
            }

            // Check accepter's balance
            if ($acceptanceFee > 0 && TradeFeeSetting::isFeeActive('trade_acceptance')) {
                if ($accepter->token_balance < $acceptanceFee) {
                    // Revert the request status if insufficient tokens
                    $tradeRequest->update(['status' => 'pending', 'responded_at' => null]);
                    return redirect()->back()->with('error', "Insufficient tokens to accept trade. You need {$acceptanceFee} tokens. Current balance: {$accepter->token_balance} tokens.");
                }
            }

            // Charge fees to both users
            if ($requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
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

            if ($acceptanceFee > 0 && TradeFeeSetting::isFeeActive('trade_acceptance')) {
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
            $totalFeesCharged = 0;
            if ($requestFee > 0 && TradeFeeSetting::isFeeActive('trade_request')) {
                $totalFeesCharged += $requestFee;
            }
            if ($acceptanceFee > 0 && TradeFeeSetting::isFeeActive('trade_acceptance')) {
                $totalFeesCharged += $acceptanceFee;
            }

            if ($totalFeesCharged > 0) {
                $successMessage .= " Token fees charged: {$totalFeesCharged} tokens total (both users).";
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
}
