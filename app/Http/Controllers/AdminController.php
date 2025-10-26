<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use App\Models\Trade;
use App\Models\UserSkill;
use App\Models\UserReport;
use App\Models\Violation;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\TradeFeeSetting;
use App\Models\TokenTransaction;
use App\Models\FeeTransaction;
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

        // Pending user reports
        $pendingReports = UserReport::where('status', 'pending')->count();
        if ($pendingReports > 0) {
            $notifications->push([
                'id' => 'pending_reports',
                'type' => 'danger',
                'title' => 'Pending User Reports',
                'message' => "{$pendingReports} user reports need review",
                'icon' => 'flag',
                'url' => route('admin.user-reports.index'),
                'created_at' => now()
            ]);
        }

        // Recent user reports (last 24 hours)
        $recentReports = UserReport::where('created_at', '>=', now()->subDay())->count();
        if ($recentReports > 0) {
            $notifications->push([
                'id' => 'recent_reports',
                'type' => 'warning',
                'title' => 'New User Reports',
                'message' => "{$recentReports} user reports submitted in the last 24 hours",
                'icon' => 'alert-triangle',
                'url' => route('admin.user-reports.index'),
                'created_at' => now()
            ]);
        }

        // Token management notifications
        $inactiveFeeSettings = TradeFeeSetting::where('is_active', false)->count();
        if ($inactiveFeeSettings > 0) {
            $notifications->push([
                'id' => 'inactive_fee_settings',
                'type' => 'warning',
                'title' => 'Inactive Fee Settings',
                'message' => "{$inactiveFeeSettings} fee settings are currently inactive",
                'icon' => 'settings',
                'url' => route('admin.fee-settings.index'),
                'created_at' => now()
            ]);
        }

        // Recent token transactions (last 24 hours)
        $recentTokenTransactions = TokenTransaction::where('created_at', '>=', now()->subDay())->count();
        if ($recentTokenTransactions > 0) {
            $notifications->push([
                'id' => 'recent_token_transactions',
                'type' => 'info',
                'title' => 'Recent Token Activity',
                'message' => "{$recentTokenTransactions} token transactions in the last 24 hours",
                'icon' => 'coins',
                'url' => route('admin.fee-settings.index'),
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

    private function getUserReports()
    {
        // Get recent user reports (last 5 reports)
        $recentReports = UserReport::with(['reporter', 'reported'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get report statistics
        $totalReports = UserReport::count();
        $pendingReports = UserReport::where('status', 'pending')->count();
        $resolvedReports = UserReport::where('status', 'resolved')->count();
        $recentReportsCount = UserReport::where('created_at', '>=', now()->subDay())->count();

        return [
            'recent' => $recentReports,
            'stats' => [
                'total' => $totalReports,
                'pending' => $pendingReports,
                'resolved' => $resolvedReports,
                'recent' => $recentReportsCount
            ]
        ];
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

        // Get user reports data
        $userReports = $this->getUserReports();

        return view('admin.dashboard', compact('stats', 'popularSkills', 'recentActivity', 'notifications', 'userReports'));
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

        // Calculate actual revenue from token purchases
        $tokenPurchaseTransactions = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0) // Only positive quantities (purchases)
            ->get();

        $totalTokensPurchased = $tokenPurchaseTransactions->sum('quantity');
        $monthlyRevenue = $tokenPurchaseTransactions->sum('amount'); // Use actual payment amount

        // Calculate monthly revenue change
        $lastMonthTokensPurchased = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0)
            ->where('created_at', '>=', $lastMonth)
            ->sum('quantity');

        $lastMonthRevenue = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0)
            ->where('created_at', '>=', $lastMonth)
            ->sum('amount');

        $previousMonthTokensPurchased = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0)
            ->where('created_at', '>=', $lastMonth->copy()->subMonth())
            ->where('created_at', '<', $lastMonth)
            ->sum('quantity');

        $previousMonthRevenue = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0)
            ->where('created_at', '>=', $lastMonth->copy()->subMonth())
            ->where('created_at', '<', $lastMonth)
            ->sum('amount');

        // Additional revenue statistics
        $totalRevenue = $tokenPurchaseTransactions->sum('amount'); // Use actual payment amounts
        $todayTokensPurchased = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0)
            ->whereDate('created_at', today())
            ->sum('quantity');
        $todayRevenue = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0)
            ->whereDate('created_at', today())
            ->sum('amount');

        // Weekly revenue
        $weeklyTokensPurchased = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0)
            ->where('created_at', '>=', $lastWeek)
            ->sum('quantity');
        $weeklyRevenue = TokenTransaction::where('status', 'completed')
            ->where('quantity', '>', 0)
            ->where('created_at', '>=', $lastWeek)
            ->sum('amount');

        $monthlyRevenueChange = $previousMonthRevenue > 0 ?
            round((($lastMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100) : 0;

        // Token statistics
        $totalTokensInCirculation = User::sum('token_balance');
        $totalTokensLastWeek = User::where('updated_at', '<=', $lastWeek)->sum('token_balance');
        $totalTokensChange = $totalTokensLastWeek > 0 ? round((($totalTokensInCirculation - $totalTokensLastWeek) / $totalTokensLastWeek) * 100) : 0;

        // Token transactions (recent activity)
        $tokenTransactions = TokenTransaction::count();
        $tokenTransactionsLastWeek = TokenTransaction::where('created_at', '<=', $lastWeek)->count();
        $tokenTransactionsChange = $tokenTransactionsLastWeek > 0 ? round((($tokenTransactions - $tokenTransactionsLastWeek) / $tokenTransactionsLastWeek) * 100) : 0;

        // Fee settings statistics
        $activeFeeSettings = TradeFeeSetting::where('is_active', true)->count();
        $totalFeeSettings = TradeFeeSetting::count();

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
            ],
            'totalRevenue' => [
                'value' => $totalRevenue,
                'change' => 0, // Total revenue doesn't have change tracking
                'changeType' => 'neutral'
            ],
            'todayRevenue' => [
                'value' => $todayRevenue,
                'change' => 0, // Daily revenue doesn't have change tracking
                'changeType' => 'neutral'
            ],
            'weeklyRevenue' => [
                'value' => $weeklyRevenue,
                'change' => 0, // Weekly revenue doesn't have change tracking
                'changeType' => 'neutral'
            ],
            'totalTokensInCirculation' => [
                'value' => $totalTokensInCirculation,
                'change' => $totalTokensChange,
                'changeType' => $totalTokensChange >= 0 ? 'positive' : 'negative'
            ],
            'tokenTransactions' => [
                'value' => $tokenTransactions,
                'change' => $tokenTransactionsChange,
                'changeType' => $tokenTransactionsChange >= 0 ? 'positive' : 'negative'
            ],
            'activeFeeSettings' => [
                'value' => $activeFeeSettings,
                'change' => 0, // No change tracking for settings
                'changeType' => 'neutral'
            ],
            'totalFeeSettings' => [
                'value' => $totalFeeSettings,
                'change' => 0, // No change tracking for settings
                'changeType' => 'neutral'
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

        // Token-related metrics
        $tokenMetrics = [
            'totalTokensInCirculation' => User::sum('token_balance'),
            'totalTokenTransactions' => TokenTransaction::count(),
            'completedPurchases' => TokenTransaction::where('status', 'completed')->where('quantity', '>', 0)->count(),
            'totalRevenue' => TokenTransaction::where('status', 'completed')->where('quantity', '>', 0)->sum('amount'),
            'monthlyRevenue' => TokenTransaction::where('status', 'completed')->where('quantity', '>', 0)->whereMonth('created_at', now()->month)->sum('amount'),
            'weeklyRevenue' => TokenTransaction::where('status', 'completed')->where('quantity', '>', 0)->where('created_at', '>=', now()->subWeek())->sum('amount'),
            'totalFeeTransactions' => FeeTransaction::count(),
            'totalFeesCollected' => abs(FeeTransaction::where('status', 'completed')->sum('amount')),
            'activeFeeSettings' => TradeFeeSetting::where('is_active', true)->count(),
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

        // Token Purchase Trends (Last 7 Days)
        $tokenTrends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('M d');
            $count = TokenTransaction::where('status', 'completed')
                ->where('quantity', '>', 0)
                ->whereDate('created_at', now()->subDays($i))
                ->count();
            $tokenTrends[$date] = $count;
        }

        // Revenue Trends (Last 7 Days)
        $revenueTrends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('M d');
            $amount = TokenTransaction::where('status', 'completed')
                ->where('quantity', '>', 0)
                ->whereDate('created_at', now()->subDays($i))
                ->sum('amount');
            $revenueTrends[$date] = $amount;
        }

        // Fee Collection Trends (Last 7 Days)
        $feeTrends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('M d');
            $count = FeeTransaction::where('status', 'completed')
                ->whereDate('created_at', now()->subDays($i))
                ->count();
            $feeTrends[$date] = $count;
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

        // Top Token Purchasers
        $topTokenPurchasers = User::select('users.id', 'users.firstname', 'users.lastname', 'users.email', DB::raw('SUM(token_transactions.quantity) as total_purchased'), DB::raw('SUM(token_transactions.amount) as total_spent'))
            ->join('token_transactions', 'users.id', '=', 'token_transactions.user_id')
            ->where('token_transactions.status', 'completed')
            ->where('token_transactions.quantity', '>', 0)
            ->groupBy('users.id', 'users.firstname', 'users.lastname', 'users.email')
            ->orderByDesc('total_purchased')
            ->limit(10)
            ->get();

        // Recent Token Transactions
        $recentTokenTransactions = TokenTransaction::with('user')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Fee Transaction Summary
        $feeTransactionSummary = FeeTransaction::select('fee_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(ABS(amount)) as total_amount'))
            ->where('status', 'completed')
            ->groupBy('fee_type')
            ->get();

        $notifications = $this->getNotifications();
        return view('admin.reports.index', compact(
            'metrics',
            'tokenMetrics',
            'userTrends',
            'tradeTrends',
            'tokenTrends',
            'revenueTrends',
            'feeTrends',
            'topSkills',
            'topTokenPurchasers',
            'recentTokenTransactions',
            'feeTransactionSummary',
            'notifications'
        ));
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

    /**
     * Suspend user with reason and duration
     */
    public function suspendUser(Request $request, User $user)
    {
        try {
            // Prevent admins from being suspended
            if ($user->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot suspend admin users.'
                ], 403);
            }

            $request->validate([
                'violation_type' => 'required|in:suspension,permanent_ban',
                'suspension_duration' => 'required_if:violation_type,suspension|in:7_days,30_days,indefinite',
                'reason' => 'required|string|max:1000',
                'admin_notes' => 'nullable|string|max:2000'
            ]);

            // Deactivate any existing active violations
            $user->violations()->active()->update(['is_active' => false]);

            // Calculate suspension dates
            $suspensionStart = now();
            $suspensionEnd = null;

            if ($request->violation_type === 'suspension') {
                switch ($request->suspension_duration) {
                    case '7_days':
                        $suspensionEnd = $suspensionStart->copy()->addDays(7);
                        break;
                    case '30_days':
                        $suspensionEnd = $suspensionStart->copy()->addDays(30);
                        break;
                    case 'indefinite':
                        $suspensionEnd = null;
                        break;
                }
            }

            // Create violation record
            $violation = Violation::create([
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'violation_type' => $request->violation_type,
                'suspension_duration' => $request->suspension_duration,
                'reason' => $request->reason,
                'admin_notes' => $request->admin_notes,
                'suspension_start' => $suspensionStart,
                'suspension_end' => $suspensionEnd,
                'is_active' => true
            ]);

            // Update user suspension status
            $user->update([
                'is_suspended' => true,
                'suspension_start' => $suspensionStart,
                'suspension_end' => $suspensionEnd,
                'suspension_reason' => $request->reason
            ]);

            $action = $request->violation_type === 'permanent_ban' ? 'permanently banned' : 'suspended';
            $duration = $request->violation_type === 'suspension' ? " for {$request->suspension_duration}" : '';

            Log::info("User {$action} by admin", [
                'admin_user' => auth()->user()->email,
                'affected_user' => $user->email,
                'user_id' => $user->id,
                'violation_id' => $violation->id,
                'reason' => $request->reason,
                'duration' => $request->suspension_duration ?? 'permanent'
            ]);

            return response()->json([
                'success' => true,
                'message' => "User {$user->name} has been {$action}{$duration}.",
                'violation' => $violation
            ]);

        } catch (\Exception $e) {
            Log::error('Error suspending user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'admin_user' => auth()->user()->email
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while suspending the user.'
            ], 500);
        }
    }

    /**
     * Lift suspension/ban for a user
     */
    public function liftSuspension(User $user)
    {
        try {
            // Deactivate all active violations
            $user->violations()->active()->update(['is_active' => false]);

            // Update user suspension status
            $user->update([
                'is_suspended' => false,
                'suspension_start' => null,
                'suspension_end' => null,
                'suspension_reason' => null
            ]);

            Log::info("User suspension lifted by admin", [
                'admin_user' => auth()->user()->email,
                'affected_user' => $user->email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "Suspension lifted for user {$user->name}."
            ]);

        } catch (\Exception $e) {
            Log::error('Error lifting suspension', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'admin_user' => auth()->user()->email
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while lifting the suspension.'
            ], 500);
        }
    }

    /**
     * Get user violation history
     */
    public function getUserViolations(User $user)
    {
        $violations = $user->violations()
            ->with('admin:id,firstname,lastname,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'violations' => $violations
        ]);
    }

    /**
     * Show fee settings management page
     */
    public function feeSettingsIndex()
    {
        $feeSettings = TradeFeeSetting::orderBy('fee_type')->get();
        $notifications = $this->getNotifications();

        return view('admin.fee-settings.index', compact('feeSettings', 'notifications'));
    }

    /**
     * Update fee setting
     */
    public function updateFeeSetting(Request $request, TradeFeeSetting $feeSetting)
    {
        try {
            // Log the incoming request data for debugging
            Log::info('Fee setting update request', [
                'fee_setting_id' => $feeSetting->id,
                'request_data' => $request->all(),
                'content_type' => $request->header('Content-Type'),
                'admin_user' => auth()->user()->email
            ]);

            $request->validate([
                'fee_amount' => 'required|integer|min:0|max:1000',
                'is_active' => 'required|boolean',
                'description' => 'nullable|string|max:500'
            ]);

            $feeSetting->update([
                'fee_amount' => $request->fee_amount,
                'is_active' => $request->is_active,
                'description' => $request->description
            ]);

            Log::info('Fee setting updated by admin', [
                'admin_user' => auth()->user()->email,
                'fee_type' => $feeSetting->fee_type,
                'new_amount' => $request->fee_amount,
                'is_active' => $request->is_active
            ]);

            // Handle both JSON and form requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Fee setting for {$feeSetting->fee_type} updated successfully.",
                    'fee_setting' => $feeSetting->fresh()
                ]);
            } else {
                return redirect()->route('admin.fee-settings.index')
                    ->with('success', "Fee setting for {$feeSetting->fee_type} updated successfully.");
            }

        } catch (\Exception $e) {
            Log::error('Error updating fee setting', [
                'error' => $e->getMessage(),
                'fee_setting_id' => $feeSetting->id,
                'admin_user' => auth()->user()->email
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the fee setting.'
                ], 500);
            } else {
                return redirect()->route('admin.fee-settings.index')
                    ->with('error', 'An error occurred while updating the fee setting.');
            }
        }
    }

    /**
     * Create new fee setting
     */
    public function createFeeSetting(Request $request)
    {
        try {
            $request->validate([
                'fee_type' => 'required|string|max:100|unique:trade_fee_settings,fee_type',
                'fee_amount' => 'required|integer|min:0|max:1000',
                'is_active' => 'required|boolean',
                'description' => 'nullable|string|max:500'
            ]);

            $feeSetting = TradeFeeSetting::create([
                'fee_type' => $request->fee_type,
                'fee_amount' => $request->fee_amount,
                'is_active' => $request->is_active,
                'description' => $request->description
            ]);

            Log::info('New fee setting created by admin', [
                'admin_user' => auth()->user()->email,
                'fee_type' => $request->fee_type,
                'fee_amount' => $request->fee_amount,
                'is_active' => $request->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => "New fee setting '{$request->fee_type}' created successfully.",
                'fee_setting' => $feeSetting
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating fee setting', [
                'error' => $e->getMessage(),
                'admin_user' => auth()->user()->email
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the fee setting.'
            ], 500);
        }
    }

    /**
     * Delete fee setting
     */
    public function deleteFeeSetting(TradeFeeSetting $feeSetting)
    {
        try {
            $feeType = $feeSetting->fee_type;
            $feeSetting->delete();

            Log::info('Fee setting deleted by admin', [
                'admin_user' => auth()->user()->email,
                'fee_type' => $feeType
            ]);

            return response()->json([
                'success' => true,
                'message' => "Fee setting '{$feeType}' deleted successfully."
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting fee setting', [
                'error' => $e->getMessage(),
                'fee_setting_id' => $feeSetting->id,
                'admin_user' => auth()->user()->email
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the fee setting.'
            ], 500);
        }
    }

    /**
     * Display announcements management page
     */
    public function announcementsIndex()
    {
        $announcements = Announcement::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $notifications = $this->getNotifications();

        return view('admin.announcements.index', compact('announcements', 'notifications'));
    }

    /**
     * Show the form for creating a new announcement
     */
    public function announcementsCreate()
    {
        $notifications = $this->getNotifications();
        return view('admin.announcements.create', compact('notifications'));
    }

    /**
     * Store a newly created announcement
     */
    public function announcementsStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:info,warning,success,danger',
            'priority' => 'required|in:low,medium,high,urgent',
            'starts_at' => 'nullable|date|after_or_equal:now',
            'expires_at' => 'nullable|date|after:starts_at'
        ]);

        $announcement = Announcement::create([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'priority' => $request->priority,
            'is_active' => $request->boolean('is_active', true),
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'created_by' => auth()->id()
        ]);

        Log::info('Announcement created', [
            'announcement_id' => $announcement->id,
            'title' => $announcement->title,
            'created_by' => auth()->user()->email
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    /**
     * Show the form for editing an announcement
     */
    public function announcementsEdit(Announcement $announcement)
    {
        $notifications = $this->getNotifications();
        return view('admin.announcements.edit', compact('announcement', 'notifications'));
    }

    /**
     * Update the specified announcement
     */
    public function announcementsUpdate(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:info,warning,success,danger',
            'priority' => 'required|in:low,medium,high,urgent',
            'starts_at' => 'nullable|date|after_or_equal:now',
            'expires_at' => 'nullable|date|after:starts_at'
        ]);

        $announcement->update([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'priority' => $request->priority,
            'is_active' => $request->boolean('is_active', true),
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at
        ]);

        Log::info('Announcement updated', [
            'announcement_id' => $announcement->id,
            'title' => $announcement->title,
            'updated_by' => auth()->user()->email
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Delete an announcement
     */
    public function announcementsDestroy(Announcement $announcement)
    {
        $title = $announcement->title;
        $announcement->delete();

        Log::info('Announcement deleted', [
            'announcement_title' => $title,
            'deleted_by' => auth()->user()->email
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    /**
     * Toggle announcement active status
     */
    public function announcementsToggle(Announcement $announcement)
    {
        $announcement->update(['is_active' => !$announcement->is_active]);

        $status = $announcement->is_active ? 'activated' : 'deactivated';
        
        Log::info("Announcement {$status}", [
            'announcement_id' => $announcement->id,
            'title' => $announcement->title,
            'updated_by' => auth()->user()->email
        ]);

        return response()->json([
            'success' => true,
            'message' => "Announcement {$status} successfully.",
            'is_active' => $announcement->is_active
        ]);
    }

    /**
     * Get active announcements for users
     */
    public static function getActiveAnnouncements()
    {
        return Announcement::active()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread announcement count for a user
     */
    public static function getUnreadAnnouncementCount($userId)
    {
        $activeAnnouncements = Announcement::active()->pluck('id');
        
        if ($activeAnnouncements->isEmpty()) {
            return 0;
        }

        $readAnnouncements = AnnouncementRead::where('user_id', $userId)
            ->whereIn('announcement_id', $activeAnnouncements)
            ->pluck('announcement_id');

        return $activeAnnouncements->diff($readAnnouncements)->count();
    }

    /**
     * Mark announcement as read by user
     */
    public function markAnnouncementAsRead(Request $request, Announcement $announcement)
    {
        $announcement->markAsReadBy(auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Announcement marked as read.'
        ]);
    }
}
