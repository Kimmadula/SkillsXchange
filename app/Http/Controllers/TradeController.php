<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Skill;
use App\Models\Trade;
use App\Models\TradeRequest;

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

        return redirect()->route('trades.matches')->with('success', 'Trade posted.');
    }

    public function matches()
    {
        $user = Auth::user();
        
        // Additional check to prevent admin users from accessing matches
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Admin users cannot access user trading functionality.');
        }
        
        // Get user's skill
        $userSkill = $user->skill;
        if (!$userSkill) {
            return view('trades.matches', ['trades' => collect([]), 'noSkill' => true]);
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
                
                return $trade;
            })
            ->sortByDesc('compatibility_score');

        return view('trades.matches', compact('trades'));
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
        
        // Gender compatibility
        if ($trade->gender_pref && $user->gender) {
            if ($trade->gender_pref === 'any' || $trade->gender_pref === $user->gender) {
                $score += 20; // High score for gender match
            } else {
                $score += 0; // No score for gender mismatch
            }
        } else {
            $score += 10; // Neutral score if no gender preference specified
        }
        
        // Location compatibility (if both have locations)
        if ($trade->location && $user->address) {
            if (str_contains(strtolower($user->address), strtolower($trade->location)) ||
                str_contains(strtolower($trade->location), strtolower($user->address))) {
                $score += 30; // High score for location match
            }
        } else {
            $score += 10; // Neutral score if no location specified
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
        
        // If user has a trade with time preferences, check for overlap
        $userTrade = Trade::where('user_id', $user->id)->first();
        
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
        
        // If user has a trade with day preferences, check for overlap
        $userTrade = Trade::where('user_id', $user->id)->first();
        
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
        
        // If user has a trade with date preferences, check for overlap
        $userTrade = Trade::where('user_id', $user->id)->first();
        
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
            ->get();
            
        // Get outgoing requests (pending and declined for user to see)
        $outgoing = TradeRequest::with(['trade', 'trade.user'])
            ->where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'declined'])
            ->latest()
            ->get();
            
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
        return view('trades.notifications', compact('notifications'));
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
                'requester_name' => $user->firstname . ' ' . $user->lastname,
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
        
        return redirect()->back()->with('success', 'Trade request sent successfully!');
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
                'trade_owner_name' => $user->firstname . ' ' . $user->lastname,
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
        
        // If accepted, update trade status to ongoing
        if ($status === 'accepted') {
            $tradeRequest->trade->update(['status' => 'ongoing']);
        }
        
        $statusText = $status === 'accepted' ? 'accepted' : 'declined';
        return redirect()->back()->with('success', "Trade request {$statusText} successfully!");
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
