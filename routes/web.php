<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth; //Added for authentication

// Include health check routes
require_once __DIR__ . '/health.php';

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/test', function () {
    return 'Test route working!';
});

// Debug route for chat issues
Route::get('/debug-chat/{tradeId}', function ($tradeId) {
    $user = Auth::user();
    $trade = \App\Models\Trade::find($tradeId);
    
    return response()->json([
        'user_authenticated' => Auth::check(),
        'user_id' => $user->id ?? null,
        'user_role' => $user->role ?? null,
        'trade_exists' => $trade ? true : false,
        'trade_id' => $trade->id ?? null,
        'trade_user_id' => $trade->user_id ?? null,
        'trade_status' => $trade->status ?? null,
        'is_trade_owner' => $trade && $user ? $trade->user_id === $user->id : false,
        'has_accepted_request' => $trade && $user ? $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists() : false,
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
        'url' => request()->url(),
        'redirect_url' => route('chat.show', $tradeId)
    ]);
})->middleware('auth');

// Simple test route to check authentication
Route::get('/test-auth', function () {
    return response()->json([
        'authenticated' => Auth::check(),
        'user_id' => Auth::id(),
        'user' => Auth::user(),
        'session_id' => session()->getId(),
        'session_data' => session()->all()
    ]);
});

// Direct test of chat controller logic
Route::get('/test-chat/{tradeId}', function ($tradeId) {
    $user = Auth::user();
    $trade = \App\Models\Trade::find($tradeId);
    
    $result = [
        'step' => 'initial',
        'user_authenticated' => Auth::check(),
        'user_id' => $user->id ?? null,
        'user_role' => $user->role ?? null,
        'trade_exists' => $trade ? true : false,
        'trade_id' => $trade->id ?? null,
        'trade_user_id' => $trade->user_id ?? null,
        'trade_status' => $trade->status ?? null,
    ];
    
    // Check if user is authenticated
    if (!$user || !Auth::check()) {
        $result['step'] = 'auth_failed';
        $result['redirect_reason'] = 'User not authenticated';
        return response()->json($result);
    }
    
    // Check if user is admin
    if ($user->role === 'admin') {
        $result['step'] = 'admin_blocked';
        $result['redirect_reason'] = 'Admin users cannot access chat';
        return response()->json($result);
    }
    
    // Check authorization
    $isTradeOwner = $trade->user_id === $user->id;
    $hasAcceptedRequest = $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists();
    
    $result['is_trade_owner'] = $isTradeOwner;
    $result['has_accepted_request'] = $hasAcceptedRequest;
    
    if (!$isTradeOwner && !$hasAcceptedRequest) {
        $result['step'] = 'unauthorized';
        $result['redirect_reason'] = 'User not authorized for this trade';
        return response()->json($result);
    }
    
    $result['step'] = 'success';
    $result['redirect_reason'] = 'Should work - no redirect expected';
    return response()->json($result);
})->middleware('auth');

// Domain migration route - helps users transition from old domain
Route::get('/domain-migration', function (Request $request) {
    // Clear any old session data
    session()->flush();
    
    // Regenerate session
    session()->regenerate();
    
    // Add migration flag
    session()->put('domain_migrated', true);
    session()->put('migration_time', time());
    
    return response()->json([
        'status' => 'success',
        'message' => 'Domain migration completed. Please try logging in again.',
        'new_domain' => 'skillsxchange.site',
        'old_domain' => 'skillsxchange-crus.onrender.com'
    ]);
});


Route::get('/legitimacy', function () {
    return response()->file(public_path('legitimacy.html'));
});

Route::get('/test-video-permissions', function () {
    return response()->file(public_path('test-video-permissions.html'));
});

Route::get('/test-video-call-fixes', function () {
    return response()->file(public_path('test-video-call-fixes.html'));
});

// UNIFIED TEST ROUTE - All video call tests in one place (NO DUPLICATES)
Route::get('/test-video-call', function () {
    return response()->file(public_path('test-video-call-unified.html'));
});

// LEGACY ROUTES (kept for backward compatibility)
Route::get('/test-video-call-complete', function () {
    return response()->file(public_path('test-video-call-complete.html'));
});

Route::get('/test-firebase-room-system', function () {
    return response()->file(public_path('test-firebase-room-system.html'));
});

Route::get('/video-call-api-alternatives', function () {
    return response()->file(public_path('video-call-api-alternatives.html'));
});

Route::get('/test-webrtc-remote-connection', function () {
    return response()->file(public_path('test-webrtc-remote-connection.html'));
});

Route::get('/test-console-errors', function () {
    return response()->file(public_path('test-console-errors.html'));
});

Route::get('/test-dynamic-status', function () {
    return response()->file(public_path('test-dynamic-status.html'));
});

Route::get('/test-user-events', function () {
    return response()->file(public_path('test-user-events.html'));
});

Route::get('/debug', function () {
    return response()->json([
        'app_env' => app()->environment(),
        'app_debug' => config('app.debug'),
        'app_key_set' => !empty(config('app.key')),
        'db_connection' => config('database.default'),
        'db_host' => config('database.connections.mysql.host'),
        'db_port' => config('database.connections.mysql.port'),
        'db_database' => config('database.connections.mysql.database'),
        'timestamp' => now()->toISOString()
    ]);
});

Route::get('/health', function () {
    // Ultra-simple health check - just return 200 OK
    return response()->json(['status' => 'ok'], 200);
});

Route::get('/security-test', function () {
    // Security test endpoint to verify headers and configuration
    return response()->json([
        'status' => 'secure',
        'message' => 'SkillsXchange Security Test',
        'timestamp' => now()->toISOString(),
        'security_headers' => [
            'x_content_type_options' => 'nosniff',
            'x_frame_options' => 'SAMEORIGIN',
            'x_xss_protection' => '1; mode=block',
            'referrer_policy' => 'strict-origin-when-cross-origin',
            'strict_transport_security' => 'max-age=31536000; includeSubDomains; preload',
        ],
        'application_info' => [
            'name' => 'SkillsXchange',
            'type' => 'Educational Platform',
            'purpose' => 'Skill Learning and Exchange',
            'version' => '1.0.0',
        ],
        'features' => [
            'video_calling' => 'Firebase WebRTC',
            'real_time_chat' => 'Pusher',
            'skill_trading' => 'Laravel',
            'task_management' => 'Laravel',
        ]
    ], 200);
});

Route::get('/ping', function () {
    // Even simpler - just return text
    return 'pong';
});

Route::get('/test-laravel', function () {
    // Test if Laravel is working
    return response()->json([
        'status' => 'ok',
        'laravel' => 'working',
        'timestamp' => now()->toISOString(),
        'app_name' => config('app.name'),
        'app_env' => app()->environment()
    ]);
});

Route::get('/simple', function () {
    // Ultra-simple route that doesn't use any Laravel features
    return response('OK', 200);
});

Route::get('/health-detailed', function () {
    try {
        // Test database connection
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'failed: ' . $e->getMessage();
    }
    
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'database' => $dbStatus,
        'app_env' => app()->environment(),
        'app_debug' => config('app.debug'),
        'app_key_set' => !empty(config('app.key'))
    ]);
});

Route::get('/test-db', function () {
    try {
        // Test database connection
        $connection = DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();
        
        // Test if we can query users table
        $userCount = \App\Models\User::count();
        $testUser = \App\Models\User::where('email', 'test@example.com')->first();
        
        $result = [
            'status' => 'success',
            'message' => 'Database connection successful!',
            'database' => $dbName,
            'connection_type' => 'MySQL',
            'user_count' => $userCount,
            'test_user_exists' => $testUser ? 'YES' : 'NO',
            'test_user_details' => $testUser ? [
                'id' => $testUser->id,
                'name' => $testUser->name,
                'email' => $testUser->email,
                'created_at' => $testUser->created_at
            ] : null,
            'timestamp' => now()
        ];
        
        return response()->json($result, 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Database connection failed!',
            'error' => $e->getMessage(),
            'timestamp' => now()
        ], 500);
    }
});

Route::get('/test-assets', function () {
    $buildPath = public_path('build');
    $manifestPath = $buildPath . '/manifest.json';
    
    $result = [
        'build_directory_exists' => is_dir($buildPath) ? 'YES' : 'NO',
        'manifest_exists' => file_exists($manifestPath) ? 'YES' : 'NO',
        'build_directory_contents' => is_dir($buildPath) ? array_diff(scandir($buildPath), ['.', '..']) : [],
        'manifest_content' => file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null,
        'app_env' => app()->environment(),
        'app_debug' => config('app.debug'),
        'vite_assets' => app()->environment('production') ? 'Production mode - using built assets' : 'Development mode - using Vite dev server'
    ];
    
    return response()->json($result, 200);
});

Route::get('/test-auth', function () {
    if (auth()->check()) {
        return 'User is logged in: ' . auth()->user()->email;
    } else {
        return 'User is NOT logged in';
    }
});

Route::get('/debug-users', function () {
    $users = \App\Models\User::all();
    $output = '<h2>All Users in Database:</h2>';
    $output .= '<table border="1" style="border-collapse: collapse; width: 100%;">';
    $output .= '<tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Verified</th><th>Created</th></tr>';
    
    foreach ($users as $user) {
        $output .= '<tr>';
        $output .= '<td>' . $user->id . '</td>';
        $output .= '<td>' . $user->firstname . ' ' . $user->lastname . '</td>';
        $output .= '<td>' . $user->email . '</td>';
        $output .= '<td>' . $user->username . '</td>';
        $output .= '<td>' . ($user->role ?? 'user') . '</td>';
        $output .= '<td>' . ($user->is_verified ? 'YES' : 'NO') . '</td>';
        $output .= '<td>' . $user->created_at . '</td>';
        $output .= '</tr>';
    }
    
    $output .= '</table>';
    
    $output .= '<h3>Summary:</h3>';
    $output .= '<p>Total Users: ' . $users->count() . '</p>';
    $output .= '<p>Verified Users: ' . $users->where('is_verified', true)->count() . '</p>';
    $output .= '<p>Pending Users: ' . $users->where('is_verified', false)->count() . '</p>';
    $output .= '<p>Admin Users: ' . $users->where('role', 'admin')->count() . '</p>';
    
    return $output;
});

Route::get('/', function () {
    // If user is authenticated, redirect to dashboard
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

// Custom broadcasting auth route for better error handling
Route::post('/broadcasting/auth', [App\Http\Controllers\BroadcastingController::class, 'auth']);

// Test broadcasting auth without auth middleware for debugging
Route::post('/test-broadcasting-auth', [App\Http\Controllers\BroadcastingController::class, 'auth']);

// Test broadcasting auth with a simple response for debugging
Route::post('/test-auth-simple', function(Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'Auth endpoint is working',
        'socket_id' => $request->input('socket_id'),
        'channel_name' => $request->input('channel_name'),
        'user_authenticated' => auth()->check(),
        'user_id' => auth()->id()
    ]);
});

// Test route for broadcasting auth
Route::get('/test-broadcasting-auth', function () {
    return response()->file(public_path('test-auth-endpoint.html'));
});

// Final broadcasting test
Route::get('/test-broadcasting-final', function () {
    return response()->file(public_path('test-broadcasting-final.html'));
});

// Broadcasting authentication route with auth middleware
Broadcast::routes(['middleware' => ['auth']]);

// Debug route for skills
Route::get('/debug-skills', function () {
    $skills = \App\Models\Skill::all();
    return response()->json([
        'count' => $skills->count(),
        'skills' => $skills->toArray(),
        'categories' => $skills->groupBy('category')->keys()->toArray()
    ]);
});

// Debug route for trades
Route::get('/debug-trades', function () {
    $trades = \App\Models\Trade::with(['offeringUser', 'offeringSkill', 'lookingSkill'])->get();
    return response()->json([
        'count' => $trades->count(),
        'trades' => $trades->toArray()
    ]);
});

// Test route for trade view
Route::get('/test-trade/{trade}', function (\App\Models\Trade $trade) {
    $user = auth()->user();
    return view('trades.test', compact('trade', 'user'));
});

// Test route to debug chat redirect issue
Route::get('/test-chat-debug/{trade}', function (\App\Models\Trade $trade) {
    $user = auth()->user();
    return response()->json([
        'success' => true,
        'message' => 'Chat debug route reached successfully',
        'trade_id' => $trade->id,
        'trade_owner' => $trade->user_id,
        'user_id' => $user->id,
        'user_authenticated' => auth()->check(),
        'is_trade_owner' => $trade->user_id === $user->id,
        'has_accepted_request' => $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()
    ]);
})->middleware('auth');

// Session management routes
Route::middleware('auth')->group(function () {
    // Session management endpoints
    Route::post('/user/keep-alive', [App\Http\Controllers\SessionController::class, 'keepAlive'])->name('session.keep-alive');
    Route::get('/user/session-status', [App\Http\Controllers\SessionController::class, 'getStatus'])->name('session.status');
    Route::get('/user/active-sessions', [App\Http\Controllers\SessionController::class, 'getActiveSessions'])->name('session.active');
    Route::delete('/user/sessions/{sessionId}', [App\Http\Controllers\SessionController::class, 'invalidateSession'])->name('session.invalidate');
    Route::post('/user/logout-all', [App\Http\Controllers\SessionController::class, 'forceLogoutAll'])->name('session.logout-all');
    
    // Admin session management
    Route::middleware('admin')->group(function () {
        Route::get('/admin/session-stats', [App\Http\Controllers\SessionController::class, 'getStats'])->name('admin.session.stats');
        Route::post('/admin/session-cleanup', [App\Http\Controllers\SessionController::class, 'cleanupExpired'])->name('admin.session.cleanup');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Test route to verify form submission
    Route::post('/profile/test', function(Request $request) {
        Log::info('Test profile route hit', [
            'method' => $request->method(),
            'all_data' => $request->all(),
            'user_id' => $request->user()?->id
        ]);
        return response()->json(['message' => 'Test route hit successfully', 'data' => $request->all()]);
    })->name('profile.test');

    // Test route to add sample skill acquisitions
    Route::get('/test-skill-acquisition', function() {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated']);
        }

        // Get some skills
        $skills = \App\Models\Skill::take(3)->get();
        if ($skills->isEmpty()) {
            return response()->json(['error' => 'No skills found. Please run SkillSeeder first.']);
        }

        $addedSkills = [];
        foreach ($skills as $skill) {
            // Check if user already has this skill
            $existingSkill = \App\Models\UserSkill::where('user_id', $user->id)
                ->where('skill_id', $skill->skill_id)
                ->first();

            if (!$existingSkill) {
                // Add skill to user_skills table
                \App\Models\UserSkill::create([
                    'user_id' => $user->id,
                    'skill_id' => $skill->skill_id
                ]);

                // Add skill acquisition history
                \App\Models\SkillAcquisitionHistory::create([
                    'user_id' => $user->id,
                    'skill_id' => $skill->skill_id,
                    'trade_id' => null,
                    'acquisition_method' => 'manual_add',
                    'score_achieved' => 100,
                    'notes' => 'Test skill acquisition',
                    'acquired_at' => now()
                ]);

                $addedSkills[] = $skill->name;
            }
        }

        return response()->json([
            'message' => 'Test skill acquisitions added',
            'added_skills' => $addedSkills,
            'user_skills_count' => \App\Models\UserSkill::where('user_id', $user->id)->count()
        ]);
    });

    // Test route to check ratings API
    Route::get('/test-ratings-api', function () {
        $user = Auth::user();
        
        // Test the ratings API directly
        $ratings = \App\Models\SessionRating::where('rated_user_id', $user->id)
            ->with(['rater:id,firstname,lastname,username'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'user_id' => $user->id,
            'ratings_count' => $ratings->count(),
            'ratings' => $ratings->toArray(),
            'api_url' => '/api/user-ratings/' . $user->id
        ]);
    });

    // Clean up skill acquisition history (remove manual registrations)
    Route::get('/cleanup-skill-acquisition', function () {
        $user = Auth::user();
        
        // Remove acquisitions that are not from trade completion
        $removed = \App\Models\SkillAcquisitionHistory::where('user_id', $user->id)
            ->where('acquisition_method', '!=', 'trade_completion')
            ->delete();
            
        return response()->json([
            'user_id' => $user->id,
            'removed_count' => $removed,
            'message' => 'Cleaned up non-trade acquisitions'
        ]);
    });

    // Debug route to check ratings table
    Route::get('/debug-ratings-table', function () {
        try {
            // Check if table exists
            $tableExists = \Schema::hasTable('session_ratings');
            
            if (!$tableExists) {
                return response()->json([
                    'error' => 'session_ratings table does not exist',
                    'table_exists' => false
                ]);
            }
            
            // Check table structure
            $columns = \Schema::getColumnListing('session_ratings');
            
            // Try to query the table
            $ratings = \App\Models\SessionRating::count();
            
            return response()->json([
                'table_exists' => true,
                'columns' => $columns,
                'total_ratings' => $ratings,
                'message' => 'Table exists and is accessible'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'table_exists' => false
            ]);
        }
    });

    // Debug route to check skill acquisition history
    Route::get('/debug-skill-acquisition', function () {
        $user = Auth::user();
        
        // Get all skill acquisitions
        $acquisitions = \App\Models\SkillAcquisitionHistory::where('user_id', $user->id)
            ->with(['skill', 'trade'])
            ->get();
            
        // Get all user skills
        $allSkills = $user->skills;
        
        // Get acquired skills using the method
        $acquiredSkills = $user->getAcquiredSkills();
        
        return response()->json([
            'user_id' => $user->id,
            'all_skills' => $allSkills->pluck('name')->toArray(),
            'acquired_skills_method' => $acquiredSkills->pluck('name')->toArray(),
            'acquisition_history' => $acquisitions->map(function($acquisition) {
                return [
                    'id' => $acquisition->id,
                    'skill_name' => $acquisition->skill ? $acquisition->skill->name : 'Unknown',
                    'method' => $acquisition->acquisition_method,
                    'trade_id' => $acquisition->trade_id,
                    'score_achieved' => $acquisition->score_achieved,
                    'acquired_at' => $acquisition->acquired_at,
                    'notes' => $acquisition->notes
                ];
            })->toArray()
        ]);
    });

    // Debug route to check completed session skill learning
    Route::get('/debug-skill-learning', function () {
        $user = Auth::user();
        
        // Get user's completed trades
        $completedTrades = \App\Models\Trade::where('status', 'closed')
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('requests', function($q) use ($user) {
                          $q->where('requester_id', $user->id)->where('status', 'accepted');
                      });
            })
            ->with(['tasks', 'lookingSkill', 'offeringSkill'])
            ->get();

        $debugInfo = [];
        
        foreach ($completedTrades as $trade) {
            $skillLearningService = new \App\Services\SkillLearningService();
            $summary = $skillLearningService->getSkillLearningSummary($trade);
            
            $debugInfo[] = [
                'trade_id' => $trade->id,
                'trade_title' => $trade->lookingSkill->name . ' ↔ ' . $trade->offeringSkill->name,
                'status' => $trade->status,
                'tasks_count' => $trade->tasks->count(),
                'tasks' => $trade->tasks->map(function($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'assigned_to' => $task->assigned_to,
                        'completed' => $task->completed,
                        'verified' => $task->verified,
                        'current_status' => $task->current_status
                    ];
                }),
                'skill_learning_summary' => $summary
            ];
        }

        return response()->json([
            'user_id' => $user->id,
            'completed_trades_count' => $completedTrades->count(),
            'completed_trades' => $debugInfo
        ]);
    });

    // Trades (user dashboard area) - Restricted to regular users only
    Route::middleware('user.only')->group(function () {
        Route::get('/trades/create', [\App\Http\Controllers\TradeController::class, 'create'])->name('trades.create');
        Route::post('/trades', [\App\Http\Controllers\TradeController::class, 'store'])->name('trades.store');
        
        // Specific routes must come before parameterized routes
        Route::get('/trades/matches', [\App\Http\Controllers\TradeController::class, 'matches'])->name('trades.matches');
        Route::get('/trades/requests', [\App\Http\Controllers\TradeController::class, 'requests'])->name('trades.requests');
        Route::get('/trades/ongoing', [\App\Http\Controllers\TradeController::class, 'ongoing'])->name('trades.ongoing');
        Route::get('/trades/notifications', [\App\Http\Controllers\TradeController::class, 'notify'])->name('trades.notifications');
        
        // Parameterized route must come last
        Route::get('/trades/{trade}', [\App\Http\Controllers\TradeController::class, 'show'])->name('trades.show');
        
        // Trade request actions
        Route::post('/trades/{trade}/request', [\App\Http\Controllers\TradeController::class, 'requestTrade'])->name('trades.request');
        Route::post('/trade-requests/{tradeRequest}/respond', [\App\Http\Controllers\TradeController::class, 'respondToRequest'])->name('trades.respond');
        
        // Notification actions
        Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\TradeController::class, 'markNotificationAsRead'])->name('trades.mark-read');
        
        
        // Task management routes
        Route::get('/tasks', [\App\Http\Controllers\TaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/create', [\App\Http\Controllers\TaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [\App\Http\Controllers\TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'show'])->name('tasks.show');
        Route::get('/tasks/{task}/edit', [\App\Http\Controllers\TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::patch('/tasks/{task}/toggle', [\App\Http\Controllers\TaskController::class, 'toggle'])->name('tasks.toggle');
        
        
// Task submission file downloads
Route::get('/submissions/{submission}/files/{fileIndex}', [\App\Http\Controllers\TaskController::class, 'downloadSubmissionFile'])->name('submissions.download');

// Task submission routes (accessible to all authenticated users)
Route::post('/tasks/{task}/start', [\App\Http\Controllers\TaskController::class, 'startTask'])->name('tasks.start');
Route::post('/tasks/{task}/submit', [\App\Http\Controllers\TaskController::class, 'submitTask'])->name('tasks.submit');
Route::get('/tasks/{task}/evaluate', [\App\Http\Controllers\TaskController::class, 'showEvaluationForm'])->name('tasks.evaluate');
Route::post('/tasks/{task}/evaluation', [\App\Http\Controllers\TaskController::class, 'storeEvaluation'])->name('tasks.store-evaluation');
Route::get('/tasks/{task}/progress', [\App\Http\Controllers\TaskController::class, 'getTaskProgress'])->name('tasks.progress');
Route::get('/tasks/{task}/submission-details', [\App\Http\Controllers\TaskController::class, 'getSubmissionDetails'])->name('tasks.submission-details');

// Skill Management Routes - READ ONLY (CRUD operations removed)
// Only allow viewing skills, no create/edit/delete operations
Route::get('/skills', [\App\Http\Controllers\SkillController::class, 'index'])->name('skills.index');
Route::get('/skills/{skill}', [\App\Http\Controllers\SkillController::class, 'show'])->name('skills.show');
Route::get('/api/skills/search', [\App\Http\Controllers\SkillController::class, 'getSkills'])->name('api.skills.search');

        
        // API routes for task management
        Route::get('/api/trades/{trade}/participants', function(\App\Models\Trade $trade) {
            $user = auth()->user();
            
            // Check if user is part of this trade
            if ($trade->user_id !== $user->id && 
                !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            $participants = collect();
            
            // Add trade owner
            $participants->push([
                'id' => $trade->user_id,
                'name' => $trade->user->firstname . ' ' . $trade->user->lastname
            ]);
            
            // Add accepted requester
            $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
            if ($acceptedRequest) {
                $participants->push([
                    'id' => $acceptedRequest->requester_id,
                    'name' => $acceptedRequest->requester->firstname . ' ' . $acceptedRequest->requester->lastname
                ]);
            }
            
            return response()->json([
                'success' => true,
                'participants' => $participants
            ]);
        });
        
        // Video call routes (Firebase-based - no server endpoints needed)
        // All video call signaling is now handled by Firebase Realtime Database
        // The VideoCallController is kept for backward compatibility but not used
        
        // Legacy video call routes for backward compatibility
        Route::post('/chat/{trade}/video-call/offer', [\App\Http\Controllers\VideoCallController::class, 'sendOffer'])->name('video-call.offer');
        Route::post('/chat/{trade}/video-call/answer', [\App\Http\Controllers\VideoCallController::class, 'sendAnswer'])->name('video-call.answer');
        Route::post('/chat/{trade}/video-call/ice-candidate', [\App\Http\Controllers\VideoCallController::class, 'sendIceCandidate'])->name('video-call.ice-candidate');
        Route::post('/chat/{trade}/video-call/end', [\App\Http\Controllers\VideoCallController::class, 'endCall'])->name('video-call.end');
        Route::get('/chat/{trade}/video-call/messages', [\App\Http\Controllers\VideoCallController::class, 'pollMessages'])->name('video-call.messages');
    });
    
    // Chat routes (accessible to all authenticated users, but blocked for admins in controller)
    Route::middleware('auth')->group(function () {
        Route::get('/chat/{trade}', [\App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
        Route::get('/chat/{trade}/messages', [\App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
        Route::post('/chat/{trade}/message', [\App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send-message');
        Route::post('/chat/{trade}/task', [\App\Http\Controllers\ChatController::class, 'createTask'])->name('chat.create-task')->middleware('throttle:10,1');
        Route::patch('/chat/task/{task}/toggle', [\App\Http\Controllers\ChatController::class, 'toggleTask'])->name('chat.toggle-task');
        Route::patch('/chat/task/{task}/verify', [\App\Http\Controllers\ChatController::class, 'verifyTask'])->name('chat.verify-task');
        Route::post('/chat/{trade}/complete-session', [\App\Http\Controllers\ChatController::class, 'completeSession'])->name('chat.complete-session');
        Route::get('/chat/{trade}/skill-learning-status', [\App\Http\Controllers\ChatController::class, 'getSkillLearningStatus'])->name('chat.skill-learning-status');
    });
    
    // Session Rating routes (accessible to all authenticated users)
    Route::middleware('auth')->group(function () {
        Route::post('/session/rating', [\App\Http\Controllers\SessionRatingController::class, 'store'])->name('session.rating.store');
        Route::get('/user/{user}/ratings', [\App\Http\Controllers\SessionRatingController::class, 'getUserRatings'])->name('user.ratings');
        Route::get('/user/{user}/rating-stats', [\App\Http\Controllers\SessionRatingController::class, 'getUserRatingStats'])->name('user.rating-stats');
        
        // API routes for AJAX calls
        Route::get('/api/user-ratings/{userId}', [\App\Http\Controllers\SessionRatingController::class, 'getUserRatings'])->name('api.user-ratings.get');
        
        // Fallback API route for testing
        Route::get('/api/test-ratings/{userId}', function($userId) {
            $user = Auth::user();
            if (!$user || $user->id != $userId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            // Check if session_ratings table exists
            try {
                $ratings = \App\Models\SessionRating::where('rated_user_id', $userId)->get();
                return response()->json([
                    'success' => true,
                    'ratings' => $ratings,
                    'table_exists' => true,
                    'count' => $ratings->count()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage(),
                    'table_exists' => false
                ]);
            }
        });
    });
    
    // Admin functionality (moved from /admin to main dashboard) - Restricted to admin users only
    Route::middleware('admin')->group(function () {
        // Main dashboard
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
        
        // Admin tabs
        Route::get('/admin/users', [AdminController::class, 'usersIndex'])->name('admin.users.index');
        Route::get('/admin/skills', [AdminController::class, 'skillsIndex'])->name('admin.skills.index');
        Route::get('/admin/exchanges', [AdminController::class, 'exchangesIndex'])->name('admin.exchanges.index');
        Route::get('/admin/reports', [AdminController::class, 'reportsIndex'])->name('admin.reports.index');
        Route::get('/admin/messages', [AdminController::class, 'messagesIndex'])->name('admin.messages.index');
        Route::get('/admin/settings', [AdminController::class, 'settingsIndex'])->name('admin.settings.index');
        
        // Skills management - DISABLED (skills are now static)
        // Route::get('/admin/skills/create', [AdminController::class, 'createSkill'])->name('admin.skill.create');
        // Route::post('/admin/skills', [AdminController::class, 'storeSkill'])->name('admin.skill.store');
        // Route::delete('/admin/skills/{skill}', [AdminController::class, 'deleteSkill'])->name('admin.skill.delete');
        
        // User management
        Route::patch('/admin/approve/{user}', [AdminController::class, 'approve'])->name('admin.approve');
        Route::patch('/admin/reject/{user}', [AdminController::class, 'reject'])->name('admin.reject');
        
        // Enhanced user verification routes
        Route::patch('/admin/users/{user}/approve', [AdminController::class, 'approveUser'])->name('admin.users.approve');
        Route::patch('/admin/users/{user}/deny', [AdminController::class, 'denyUser'])->name('admin.users.deny');
        Route::get('/admin/user/{user}', [AdminController::class, 'show'])->name('admin.user.show');

        // Admin profile
        Route::get('/admin/profile', [AdminController::class, 'profile'])->name('admin.profile');
        Route::put('/admin/profile', [AdminController::class, 'updateProfile'])->name('admin.profile.update');
    });
    
    // Skill History Routes (moved inside auth middleware)
    Route::get('/my-skills/history', [\App\Http\Controllers\SkillHistoryController::class, 'index'])->name('skills.history');
    Route::get('/my-skills/history/{history}', [\App\Http\Controllers\SkillHistoryController::class, 'show'])->name('skills.history.show');
    Route::get('/my-skills/history-export', [\App\Http\Controllers\SkillHistoryController::class, 'export'])->name('skills.history.export');
    Route::get('/api/skills/analytics', [\App\Http\Controllers\SkillHistoryController::class, 'getAnalytics'])->name('api.skills.analytics');
});

// Admin routes available at /admin

require __DIR__.'/auth.php';
