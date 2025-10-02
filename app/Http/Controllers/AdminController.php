<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use App\Models\Trade;
use App\Models\UserSkill;
use App\Http\Requests\StoreSkillRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get notifications for the admin dashboard
     */
    private function getNotifications()
    {
        $notifications = collect();

        // Pending user verifications
        $pendingUsers = User::where('is_verified', false)->count();
        if ($pendingUsers > 0) {
            $notifications->push([
                'id' => 'pending_users',
                'type' => 'warning',
                'title' => 'Pending User Verifications',
                'message' => "{$pendingUsers} users are waiting for verification",
                'icon' => 'users',
                'url' => route('admin.users.index'),
                'created_at' => now()
            ]);
        }

        // Recent user registrations (last 24 hours)
        $recentUsers = User::where('created_at', '>=', now()->subDay())->count();
        if ($recentUsers > 0) {
            $notifications->push([
                'id' => 'recent_users',
                'type' => 'info',
                'title' => 'New User Registrations',
                'message' => "{$recentUsers} new users registered in the last 24 hours",
                'icon' => 'user-plus',
                'url' => route('admin.users.index'),
                'created_at' => now()
            ]);
        }

        // Active trades
        $activeTrades = Trade::whereIn('status', ['open', 'ongoing'])->count();
        if ($activeTrades > 0) {
            $notifications->push([
                'id' => 'active_trades',
                'type' => 'success',
                'title' => 'Active Trades',
                'message' => "{$activeTrades} trades are currently active",
                'icon' => 'exchange',
                'url' => route('admin.exchanges.index'),
                'created_at' => now()
            ]);
        }

        // System maintenance reminder (example)
        $notifications->push([
            'id' => 'maintenance_reminder',
            'type' => 'warning',
            'title' => 'System Maintenance',
            'message' => 'Scheduled maintenance window: Sunday 2:00 AM - 4:00 AM',
            'icon' => 'settings',
            'url' => route('admin.settings.index'),
            'created_at' => now()->subHours(2)
        ]);

        return $notifications->sortByDesc('created_at');
    }

    public function index()
    {
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        // Get popular skills with user counts
        $popularSkills = $this->getPopularSkills();
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity();
        $notifications = $this->getNotifications();
        
        return view('admin.dashboard', compact('stats', 'popularSkills', 'recentActivity', 'notifications'));
    }
    
    private function getDashboardStats()
    {
        $now = Carbon::now();
        $lastWeek = $now->copy()->subWeek();
        $lastMonth = $now->copy()->subMonth();
        
        // Total users
        $totalUsers = User::count();
        $totalUsersLastWeek = User::where('created_at', '<=', $lastWeek)->count();
        $totalUsersChange = $totalUsersLastWeek > 0 ? round((($totalUsers - $totalUsersLastWeek) / $totalUsersLastWeek) * 100) : 0;
        
        // Active users (users who have logged in within last 7 days)
        $activeUsers = User::where('updated_at', '>=', $lastWeek)->count();
        $activeUsersLastWeek = User::where('updated_at', '>=', $lastWeek->copy()->subWeek())
                                  ->where('updated_at', '<', $lastWeek)->count();
        $activeUsersChange = $activeUsersLastWeek > 0 ? round((($activeUsers - $activeUsersLastWeek) / $activeUsersLastWeek) * 100) : 0;
        
        // Total skills (skills table doesn't have timestamps)
        $totalSkills = Skill::count();
        $totalSkillsLastWeek = $totalSkills; // Since skills don't have timestamps, we'll use current count
        $totalSkillsChange = 0; // No change tracking for skills without timestamps
        
        // Skill exchanges (trades)
        $skillExchanges = Trade::count();
        $skillExchangesLastWeek = Trade::where('created_at', '<=', $lastWeek)->count();
        $skillExchangesChange = $skillExchangesLastWeek > 0 ? round((($skillExchanges - $skillExchangesLastWeek) / $skillExchangesLastWeek) * 100) : 0;
        
        // Monthly revenue (placeholder - you can implement actual revenue tracking)
        $monthlyRevenue = 0; // This would come from a revenue/payment system
        $monthlyRevenueChange = 0;
        
        return [
            'totalUsers' => [
                'value' => $totalUsers,
                'change' => $totalUsersChange,
                'changeType' => $totalUsersChange >= 0 ? 'positive' : 'negative'
            ],
            'activeUsers' => [
                'value' => $activeUsers,
                'change' => $activeUsersChange,
                'changeType' => $activeUsersChange >= 0 ? 'positive' : 'negative'
            ],
            'totalSkills' => [
                'value' => $totalSkills,
                'change' => $totalSkillsChange,
                'changeType' => $totalSkillsChange >= 0 ? 'positive' : 'negative'
            ],
            'skillExchanges' => [
                'value' => $skillExchanges,
                'change' => $skillExchangesChange,
                'changeType' => $skillExchangesChange >= 0 ? 'positive' : 'negative'
            ],
            'monthlyRevenue' => [
                'value' => $monthlyRevenue,
                'change' => $monthlyRevenueChange,
                'changeType' => $monthlyRevenueChange >= 0 ? 'positive' : 'negative'
            ]
        ];
    }
    
    private function getPopularSkills()
    {
        return UserSkill::select('skills.name', 'skills.category', DB::raw('COUNT(user_skills.user_id) as user_count'))
            ->join('skills', 'user_skills.skill_id', '=', 'skills.skill_id')
            ->groupBy('skills.skill_id', 'skills.name', 'skills.category')
            ->orderBy('user_count', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($skill) {
                // Calculate change (simplified - you might want to track this over time)
                $skill->change = 0; // Placeholder
                $skill->changeType = 'neutral';
                return $skill;
            });
    }
    
    private function getRecentActivity()
    {
        $activities = collect();
        
        // Get recent user verifications
        $recentVerifications = User::where('is_verified', true)
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user_verified',
                    'title' => 'User verified: ' . $user->name,
                    'time' => $user->updated_at->diffForHumans(),
                    'role' => ucfirst($user->role),
                    'icon' => 'user-check'
                ];
            });
        
        // Get recent user registrations
        $recentRegistrations = User::where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user_registration',
                    'title' => 'New user registration: ' . $user->name,
                    'time' => $user->created_at->diffForHumans(),
                    'role' => ucfirst($user->role),
                    'icon' => 'user-plus'
                ];
            });
        
        // Get recent trades
        $recentTrades = Trade::with(['offeringSkill', 'lookingSkill', 'user'])
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($trade) {
                return [
                    'type' => 'trade_created',
                    'title' => 'New trade: ' . $trade->offeringSkill->name . ' for ' . $trade->lookingSkill->name,
                    'time' => $trade->created_at->diffForHumans(),
                    'role' => 'Trade',
                    'icon' => 'exchange'
                ];
            });
        
        // Combine and sort by time
        $activities = $activities->merge($recentVerifications)
            ->merge($recentRegistrations)
            ->merge($recentTrades)
            ->sortByDesc(function ($activity) {
                return $activity['time'];
            })
            ->take(3);
        
        return $activities->values();
    }

    public function approve(User $user)
    {
        $user->is_verified = true;
        $user->save();
        return redirect()->route('admin.dashboard')->with('success', 'User approved!');
    }
    
    public function reject(User $user)
    {
        $user->is_verified = false;
        $user->save();
        return redirect()->route('admin.dashboard')->with('success', 'User rejected!');
    }

    public function show(User $user)
    {
        return view('admin.user_show', compact('user'));
    }

    public function usersIndex()
    {
        $users = User::with('skill')->orderBy('created_at', 'desc')->paginate(20);
        $notifications = $this->getNotifications();
        return view('admin.users.index', compact('users', 'notifications'));
    }

    public function skillsIndex()
    {
        $skills = Skill::withCount('users')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
        $notifications = $this->getNotifications();
        return view('admin.skills.index', compact('skills', 'notifications'));
    }
    
    public function exchangesIndex()
    {
        $trades = Trade::with(['user', 'offeringSkill', 'lookingSkill'])
            ->withCount('requests')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        $notifications = $this->getNotifications();
        return view('admin.exchanges.index', compact('trades', 'notifications'));
    }
    
    public function reportsIndex()
    {
        // Key Metrics
        $metrics = [
            'totalUsers' => User::count(),
            'usersThisMonth' => User::whereMonth('created_at', now()->month)->count(),
            'totalTrades' => Trade::count(),
            'tradesThisMonth' => Trade::whereMonth('created_at', now()->month)->count(),
            'activeTrades' => Trade::whereIn('status', ['open', 'ongoing'])->count(),
            'ongoingTrades' => Trade::where('status', 'ongoing')->count(),
            'totalMessages' => 40, // Placeholder - would need messages table
            'pendingRequests' => 1, // Placeholder - would need requests table
        ];

        // User Registration Trends (Last 7 Days)
        $userTrends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('M d');
            $count = User::whereDate('created_at', now()->subDays($i))->count();
            $userTrends[$date] = $count;
        }

        // Trade Creation Trends (Last 7 Days)
        $tradeTrends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('M d');
            $count = Trade::whereDate('created_at', now()->subDays($i))->count();
            $tradeTrends[$date] = $count;
        }

        // Top Skills by Usage
        $topSkills = Skill::withCount('users')
            ->get()
            ->map(function ($skill) {
                $offeringCount = Trade::where('offering_skill_id', $skill->skill_id)->count();
                $lookingCount = Trade::where('looking_skill_id', $skill->skill_id)->count();
                $skill->offering_count = $offeringCount;
                $skill->looking_count = $lookingCount;
                $skill->total_usage = $skill->users_count + $offeringCount + $lookingCount;
                return $skill;
            })
            ->sortByDesc('total_usage')
            ->take(10);

        $notifications = $this->getNotifications();
        return view('admin.reports.index', compact('metrics', 'userTrends', 'tradeTrends', 'topSkills', 'notifications'));
    }
    
    public function messagesIndex()
    {
        // This would integrate with your messaging system
        $messages = collect(); // Placeholder
        $notifications = $this->getNotifications();
        return view('admin.messages.index', compact('messages', 'notifications'));
    }
    
    public function settingsIndex()
    {
        $notifications = $this->getNotifications();
        return view('admin.settings.index', compact('notifications'));
    }

    public function profile()
    {
        $user = auth()->user();
        $notifications = $this->getNotifications();
        return view('admin.profile.index', compact('user', 'notifications'));
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['firstname', 'lastname', 'middlename', 'email', 'username']);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo && file_exists(storage_path('app/public/' . $user->photo))) {
                unlink(storage_path('app/public/' . $user->photo));
            }
            
            $photo = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs('public/photos', $filename);
            $data['photo'] = 'photos/' . $filename;
        }

        $user->update($data);

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully!');
    }
    
    private function getUserGrowthReport()
    {
        $last30Days = Carbon::now()->subDays(30);
        $userGrowth = User::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $last30Days)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return $userGrowth;
    }
    
    private function getSkillPopularityReport()
    {
        return UserSkill::select('skills.name', 'skills.category', DB::raw('COUNT(user_skills.user_id) as user_count'))
            ->join('skills', 'user_skills.skill_id', '=', 'skills.skill_id')
            ->groupBy('skills.skill_id', 'skills.name', 'skills.category')
            ->orderBy('user_count', 'desc')
            ->get();
    }
    
    private function getTradeActivityReport()
    {
        $last30Days = Carbon::now()->subDays(30);
        $tradeActivity = Trade::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $last30Days)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return $tradeActivity;
    }

    public function createSkill()
    {
        return view('admin.skills.create');
    }

    public function storeSkill(StoreSkillRequest $request)
    {
        try {
            $validated = $request->validated();

            // Log the attempt for debugging
            Log::info('Admin attempting to create skill', [
                'admin_user' => auth()->user()->email,
                'skill_data' => $validated
            ]);

            // Simple duplicate check
            $normalizedName = $this->normalizeSkillName($validated['name']);
            $existingSkill = Skill::whereRaw('LOWER(name) = ?', [strtolower($normalizedName)])->first();
            
            if ($existingSkill) {
                Log::warning('Duplicate skill creation attempt', ['name' => $normalizedName]);
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['name' => 'A skill with this name already exists. Please choose a different name.']);
            }

            $skill = Skill::create($validated);
            
            Log::info('Skill created successfully', ['skill_id' => $skill->skill_id, 'name' => $skill->name]);

            return redirect()->route('admin.skills.index')->with('success', 'Skill "' . $skill->name . '" added successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error creating skill', ['error' => $e->getMessage(), 'code' => $e->getCode()]);
            
            // Handle database unique constraint violations
            if ($e->getCode() == 23000) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['name' => 'A skill with this name already exists. Please choose a different name.']);
            }
            
            // Handle other database errors
            return redirect()->back()
                ->withInput()
                ->withErrors(['database' => 'Database error: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Unexpected error creating skill', ['error' => $e->getMessage()]);
            
            // Handle any other unexpected errors
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'An unexpected error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Normalize skill name by:
     * - Trimming whitespace
     * - Converting multiple spaces to single space
     * - Converting to proper case (first letter of each word capitalized)
     */
    private function normalizeSkillName(string $name): string
    {
        // Trim whitespace
        $name = trim($name);
        
        // Replace multiple spaces with single space
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Convert to proper case (Title Case)
        $name = ucwords(strtolower($name));
        
        return $name;
    }

    public function deleteSkill(Skill $skill)
    {
        $skill->delete();
        return back()->with('success', 'Skill deleted.');
    }

    /**
     * Approve a user (AJAX endpoint)
     */
    public function approveUser(User $user)
    {
        try {
            // Prevent admins from being modified
            if ($user->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify admin users.'
                ], 403);
            }

            $user->is_verified = true;
            $user->save();

            Log::info('User approved by admin', [
                'admin_user' => auth()->user()->email,
                'approved_user' => $user->email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "User {$user->name} has been approved successfully.",
                'user' => [
                    'id' => $user->id,
                    'is_verified' => $user->is_verified,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error approving user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'admin_user' => auth()->user()->email
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while approving the user.'
            ], 500);
        }
    }

    /**
     * Deny/Revoke user verification (AJAX endpoint)
     */
    public function denyUser(User $user)
    {
        try {
            // Prevent admins from being modified
            if ($user->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify admin users.'
                ], 403);
            }

            $wasVerified = $user->is_verified;
            $user->is_verified = false;
            $user->save();

            $action = $wasVerified ? 'revoked' : 'denied';
            $actionPast = $wasVerified ? 'Verification revoked' : 'Registration denied';

            Log::info("User verification {$action} by admin", [
                'admin_user' => auth()->user()->email,
                'affected_user' => $user->email,
                'user_id' => $user->id,
                'was_verified' => $wasVerified
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$actionPast} for user {$user->name}.",
                'user' => [
                    'id' => $user->id,
                    'is_verified' => $user->is_verified,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error denying/revoking user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'admin_user' => auth()->user()->email
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the request.'
            ], 500);
        }
    }
}