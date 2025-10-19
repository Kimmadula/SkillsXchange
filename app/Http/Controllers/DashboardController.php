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
    public function index()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Please log in to access your dashboard.');
            }
            
            if ($user->role === 'admin') {
                return $this->adminDashboard();
            }
            
            return $this->userDashboard($user);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'There was an error loading your dashboard. Please log in again.');
        }
    }
    
    private function adminDashboard()
    {
        try {
            // Get pending users for admin functionality
            $pendingUsers = User::where('is_verified', false)->get();
            
            // Admin statistics
            $stats = [
                'activeTrades' => Trade::where('status', 'ongoing')->count(),
                'completedTrades' => Trade::where('status', 'closed')->count(),
                'pendingTrades' => Trade::where('status', 'open')->count(),
                'pendingUsers' => $pendingUsers->count(),
                'totalTrades' => Trade::count(),
                'totalUsers' => User::count(),
                'verifiedUsers' => User::where('is_verified', true)->count(),
            ];
            
            // Get expired sessions
            $expiredSessions = Trade::where('status', 'expired')
                ->with(['offeringSkill', 'lookingSkill'])
                ->get();
            
            Log::info('AdminDashboard: Loading with ' . $pendingUsers->count() . ' pending users');
            
            return view('dashboard', compact('stats', 'pendingUsers', 'expiredSessions'));
        } catch (\Exception $e) {
            Log::error('AdminDashboard error: ' . $e->getMessage());
            // Return with empty data to prevent errors
            return view('dashboard', [
                'stats' => ['totalUsers' => 0, 'verifiedUsers' => 0, 'pendingUsers' => 0, 'totalTrades' => 0, 'activeTrades' => 0, 'completedTrades' => 0, 'pendingTrades' => 0],
                'pendingUsers' => collect(),
                'expiredSessions' => collect()
            ]);
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
            
            return view('dashboard', compact(
                'completedSessions', 
                'ongoingSessions', 
                'expiredSessions',
                'pendingRequests', 
                'declinedRequests', 
                'pendingRequestsToMe',
                'userStats'
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
                'pendingRequestsToMe' => collect()
            ]);
        }
    }
    
}
