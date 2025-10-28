<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use App\Models\Trade;
use App\Models\TradeRequest;
use App\Models\TradeTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    public static function getUnreadAnnouncementCount($userId)
    {
        $activeIds = \App\Models\Announcement::active()->pluck('id');
        if ($activeIds->isEmpty()) {
            return 0;
        }
        $read = \App\Models\AnnouncementRead::where('user_id', $userId)
            ->whereIn('announcement_id', $activeIds)
            ->pluck('announcement_id');
        return $activeIds->diff($read)->count();
    }

    public function markAnnouncementAsRead(\App\Models\Announcement $announcement)
    {
        $announcement->markAsReadBy(auth()->user());
        return response()->json(['success' => true]);
    }
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login')->with('error', 'Please log in to access your dashboard.');
            }

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return $this->userDashboard($user);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'There was an error loading your dashboard. Please log in again.');
        }
    }


    private function userDashboard($user)
    {
        try {
            $userId = $user->id;

            // Get user's trades (both posted and participated in)
            $myTrades = Trade::where('user_id', $userId)->get();
            $participatedTrades = Trade::whereHas('requests', function($query) use ($userId) {
                $query->where('requester_id', $userId)->where('status', 'accepted');
            })->get();

            // Get all trades user is involved in
            $allUserTrades = $myTrades->merge($participatedTrades)->unique('id');

            // Check for expired sessions and mark them as closed
            $expiredSessions = collect();
            foreach ($allUserTrades as $trade) {
                if ($trade->status === 'ongoing' && $trade->isExpired()) {
                    $trade->update(['status' => 'closed']);
                    $expiredSessions->push($trade);
                }
            }

            // Categorize trades
            $completedSessions = $allUserTrades->where('status', 'closed');
            $ongoingSessions = $allUserTrades->where('status', 'ongoing');

            // Get requests (exclude accepted ones from pending/declined lists)
            $myRequests = TradeRequest::where('requester_id', $userId)
                ->whereIn('status', ['pending', 'declined'])
                ->with(['trade.user', 'trade.offeringSkill', 'trade.lookingSkill'])
                ->get();

            $pendingRequests = $myRequests->where('status', 'pending');
            $declinedRequests = $myRequests->where('status', 'declined');

            // Get requests to user's trades (only pending ones)
            $requestsToMyTrades = TradeRequest::whereHas('trade', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })->where('status', 'pending')
            ->with(['requester', 'trade.offeringSkill', 'trade.lookingSkill'])
            ->get();

            $pendingRequestsToMe = $requestsToMyTrades;

            $userStats = [
                'completedSessions' => $completedSessions->count(),
                'ongoingSessions' => $ongoingSessions->count(),
                'expiredSessions' => $expiredSessions->count(),
                'pendingRequests' => $pendingRequests->count(),
                'declinedRequests' => $declinedRequests->count(),
                'pendingRequestsToMe' => $pendingRequestsToMe->count(),
            ];

            Log::info('UserDashboard: Loading for user ' . $user->id . ' with stats: ' . json_encode($userStats));

            // Fetch active announcements targeted for this user's role
            $announcements = \App\Models\Announcement::active()
                ->audienceForUser($user)
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return view('dashboard', compact(
                'completedSessions',
                'ongoingSessions',
                'expiredSessions',
                'pendingRequests',
                'declinedRequests',
                'pendingRequestsToMe',
                'userStats',
                'announcements'
            ));
        } catch (\Exception $e) {
            Log::error('UserDashboard error: ' . $e->getMessage());
            // Return with empty data to prevent errors
            return view('dashboard', [
                'userStats' => [
                    'completedSessions' => 0,
                    'ongoingSessions' => 0,
                    'expiredSessions' => 0,
                    'pendingRequests' => 0,
                    'declinedRequests' => 0,
                    'pendingRequestsToMe' => 0
                ],
                'expiredSessions' => collect(),
                'completedSessions' => collect(),
                'ongoingSessions' => collect(),
                'pendingRequests' => collect(),
                'declinedRequests' => collect(),
                'pendingRequestsToMe' => collect(),
                'announcements' => collect()
            ]);
        }
    }

}
