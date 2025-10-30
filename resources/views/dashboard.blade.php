@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5 dark-theme dashboard-scope">
    <style>
        /* Scope all dashboard styles to prevent conflicts with app.css */
        .dashboard-scope * { box-sizing: border-box; }
        .dashboard-scope .text-gradient { color: inherit; background: none; }

        /* Neutral, light cards */
        .dashboard-scope .dashboard-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .dashboard-scope .dashboard-card--stats { background: #ffffff; }
        .dashboard-scope .dashboard-card--warning { background: #fffdf5; border-color: #ffe58f; }

        /* Stat grid */
        .dashboard-scope .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .dashboard-scope .stat-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.2s ease;
        }
        .dashboard-scope .stat-card--clickable {
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            position: relative;
        }
        .dashboard-scope .stat-card--clickable:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #d1d5db;
        }
        .dashboard-scope .stat-action {
            margin-left: auto;
            opacity: 0;
            transition: opacity 0.2s ease;
            color: #6b7280;
        }
        .dashboard-scope .stat-card--clickable:hover .stat-action {
            opacity: 1;
        }
        .dashboard-scope .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center; }
        .dashboard-scope .stat-icon--success { background: #ecfdf5; color: #047857; }
        .dashboard-scope .stat-icon--primary { background: #eff6ff; color: #1d4ed8; }
        .dashboard-scope .stat-icon--warning { background: #fffbeb; color: #b45309; }
        .dashboard-scope .stat-icon--danger { background: #fef2f2; color: #b91c1c; }
        .dashboard-scope .stat-label { color: #6b7280; font-size: 0.85rem; }
        .dashboard-scope .stat-value { color: #111827; font-weight: 700; font-size: 1.25rem; }

        /* Responsive tiles */
        .dashboard-scope .responsive-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .dashboard-scope .responsive-item {
            border-radius: 12px;
            padding: 14px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }
        .dashboard-scope .responsive-item--yellow { background: #fffdf5; border-color: #ffe58f; }
        .dashboard-scope .responsive-item--blue { background: #ffffff; border-color: #bfdbfe; }
        .dashboard-scope .responsive-item--green { background: #ffffff; border-color: #bbf7d0; }
        .dashboard-scope .responsive-item--purple { background: #ffffff; border-color: #ddd6fe; }

        /* Alerts */
        .dashboard-scope .alert-responsive { display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 10px; background: #fffdf5; color: #92400e; }
        .dashboard-scope .alert-responsive--warning { border: 1px solid #ffe58f; }

        /* Utility overrides to avoid global collisions */
        .dashboard-scope .badge { display: inline-block; padding: 4px 8px; border-radius: 9999px; font-size: 12px; }
        .dashboard-scope a { text-decoration: none; }

        /* Mobile */
        @media (max-width: 480px) {
            .dashboard-scope .stats-grid { grid-template-columns: 1fr; }
            .dashboard-scope .responsive-container { grid-template-columns: 1fr; }
            .dashboard-scope .stat-card { padding: 10px; }
        }
    </style>
    <div class="container">
        <!-- Welcome Section -->
        <div class="mb-4 mb-md-5">
            <h1 class="h2 fw-bold text-gradient mb-2">Welcome back, {{ auth()->user()->firstname }}!</h1>
            <p class="text-muted">Here's what's happening with your skill trades today.</p>
        </div>

        <!-- Admin Approval Notice -->
        @if(!auth()->user()->is_verified)
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-clock me-2"></i>
                <div>
                    <strong>Account Pending Approval</strong><br>
                    <small>Your account is currently pending admin approval. You can browse the site, but some features may be limited until your account is approved by an administrator.</small>
                </div>
            </div>
        </div>
        @endif

        <!-- User Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card fade-in">
                <div class="stat-icon stat-icon--success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Completed Sessions</div>
                    <div class="stat-value">{{ isset($userStats) ? $userStats['completedSessions'] : 0 }}</div>
                </div>
            </div>

            <a href="{{ route('trades.ongoing') }}" class="stat-card fade-in stat-card--clickable">
                <div class="stat-icon stat-icon--primary">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Ongoing Sessions</div>
                    <div class="stat-value">{{ isset($userStats) ? $userStats['ongoingSessions'] : 0 }}</div>
                </div>
                <div class="stat-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="{{ route('trades.requests') }}" class="stat-card fade-in stat-card--clickable">
                <div class="stat-icon stat-icon--warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-value">{{ isset($userStats) ? $userStats['pendingRequests'] : 0 }}</div>
                </div>
                <div class="stat-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="{{ route('trades.requests') }}?status=declined" class="stat-card fade-in stat-card--clickable">
                <div class="stat-icon stat-icon--danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Declined Requests</div>
                    <div class="stat-value">{{ isset($userStats) ? $userStats['declinedRequests'] : 0 }}</div>
                </div>
                <div class="stat-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <!-- Expired Sessions -->
        @if(isset($expiredSessions) && $expiredSessions->count() > 0)
        <div class="dashboard-card dashboard-card--warning slide-up">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-exclamation-triangle me-2" style="color: var(--accent-yellow);"></i>
                <h5 class="mb-0 text-gradient">Expired Sessions ({{ $expiredSessions->count() }})</h5>
            </div>
            <div class="alert-responsive alert-responsive--warning">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Session Expired!</strong> The following sessions have passed their scheduled time and are
                    now marked as expired.
                </div>
            </div>
            <div class="responsive-container">
                @foreach($expiredSessions->take(3) as $session)
                <div class="responsive-item responsive-item--yellow">
                    <div class="text-center">
                        <h6 class="mb-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ $session->offeringSkill->name }} ↔ {{ $session->lookingSkill->name }}
                        </h6>
                        <p class="small mb-1">
                            <strong>Ended:</strong> {{ $session->end_date ?
                            \Carbon\Carbon::parse($session->end_date)->format('M d, Y') : 'N/A' }}
                        </p>
                        <span class="badge"
                            style="background: var(--accent-yellow); color: var(--bg-primary);">Expired</span>
                    </div>
                </div>
                @endforeach
            </div>
            @if($expiredSessions->count() > 3)
            <div class="text-center mt-3">
                <small class="text-muted">And {{ $expiredSessions->count() - 3 }} more expired sessions...</small>
            </div>
            @endif
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="dashboard-card dashboard-card--stats slide-up">
            <h2 class="h5 fw-bold text-gradient mb-4">Quick Actions</h2>
            <div class="responsive-container">
                @if(auth()->user()->role !== 'admin')
                <a href="{{ route('trades.create') }}" class="text-decoration-none">
                    <div class="responsive-item responsive-item--blue">
                        <div class="text-center">
                            <i class="fas fa-plus mb-2" style="font-size: 1.5rem;"></i>
                            <h6 class="mb-1">Post a Trade</h6>
                            <small>Create a new skill trade post</small>
                        </div>
                    </div>
                </a>

                <a href="{{ route('trades.matches') }}" class="text-decoration-none">
                    <div class="responsive-item responsive-item--green">
                        <div class="text-center">
                            <i class="fas fa-search mb-2" style="font-size: 1.5rem;"></i>
                            <h6 class="mb-1">Find Matches</h6>
                            <small>Browse available trades</small>
                        </div>
                    </div>
                </a>
                @endif

                <a href="{{ route('profile.show') }}" class="text-decoration-none">
                    <div class="responsive-item responsive-item--purple">
                        <div class="text-center">
                            <i class="fas fa-user mb-2" style="font-size: 1.5rem;"></i>
                            <h6 class="mb-1">Profile</h6>
                            <small>Manage your skills and info</small>
                        </div>
                    </div>
                </a>

                @if(auth()->user()->role !== 'admin')
                <a href="{{ route('trades.manage') }}" class="text-decoration-none">
                    <div class="responsive-item responsive-item--blue">
                        <div class="text-center">
                            <i class="fas fa-tasks mb-2" style="font-size: 1.5rem;"></i>
                            <h6 class="mb-1">Your Trades</h6>
                            <small>View your posted trades</small>
                        </div>
                    </div>
                </a>
                @endif
            </div>
        </div>

        @if(auth()->user()->role !== 'admin')
        <!-- User Sessions and Requests -->

        <!-- Completed Sessions -->
        @if(isset($completedSessions) && $completedSessions->count() > 0)
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Completed Sessions</h2>
                <div class="space-y-4">
                    @foreach($completedSessions->take(5) as $session)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $session->offeringSkill->name }} ↔ {{ $session->lookingSkill->name }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    @if($session->user_id === auth()->id())
                                    You offered {{ $session->offeringSkill->name }} and learned {{
                                    $session->lookingSkill->name }}
                                    @else
                                    You learned {{ $session->offeringSkill->name }} and offered {{
                                    $session->lookingSkill->name }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-2">
                                    Completed on {{ $session->updated_at->format('M d, Y') }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Completed
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Ongoing Sessions -->
        @if(isset($ongoingSessions) && $ongoingSessions->count() > 0)
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ongoing Sessions</h2>
                <div class="space-y-4">
                    @foreach($ongoingSessions->take(5) as $session)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $session->offeringSkill->name }} ↔ {{ $session->lookingSkill->name }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    @if($session->user_id === auth()->id())
                                    You're offering {{ $session->offeringSkill->name }} and learning {{
                                    $session->lookingSkill->name }}
                                    @else
                                    You're learning {{ $session->offeringSkill->name }} and offering {{
                                    $session->lookingSkill->name }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-2">
                                    Started on {{ $session->start_date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Active
                                </span>
                                <a href="{{ route('chat.show', $session->id) }}"
                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                    View Chat
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Pending Requests -->
        @if(isset($pendingRequests) && $pendingRequests->count() > 0)
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Pending Requests</h2>
                <div class="space-y-4">
                    @foreach($pendingRequests->take(5) as $request)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900">
                                    Request to {{ $request->trade->user->firstname }} {{ $request->trade->user->lastname
                                    }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    You want to learn {{ $request->trade->offeringSkill->name }} and offer {{
                                    $request->trade->lookingSkill->name }}
                                </p>
                                @if($request->message)
                                <p class="text-sm text-gray-600 mt-2 italic">"{{ $request->message }}"</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-2">
                                    Sent on {{ $request->created_at->format('M d, Y') }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Pending
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Declined Requests -->
        @if(isset($declinedRequests) && $declinedRequests->count() > 0)
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Declined Requests</h2>
                <div class="space-y-4">
                    @foreach($declinedRequests->take(5) as $request)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900">
                                    Request to {{ $request->trade->user->firstname }} {{ $request->trade->user->lastname
                                    }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    You wanted to learn {{ $request->trade->offeringSkill->name }} and offer {{
                                    $request->trade->lookingSkill->name }}
                                </p>
                                <p class="text-xs text-gray-400 mt-2">
                                    Declined on {{ $request->responded_at->format('M d, Y') }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Declined
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Requests to Your Trades -->
        @if(isset($pendingRequestsToMe) && $pendingRequestsToMe->count() > 0)
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Requests to Your Trades</h2>
                <div class="space-y-4">
                    @foreach($pendingRequestsToMe->take(5) as $request)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900">
                                    Request from {{ $request->requester->firstname }} {{ $request->requester->lastname
                                    }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Wants to learn {{ $request->trade->offeringSkill->name }} and offer {{
                                    $request->trade->lookingSkill->name }}
                                </p>
                                @if($request->message)
                                <p class="text-sm text-gray-600 mt-2 italic">"{{ $request->message }}"</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-2">
                                    Received on {{ $request->created_at->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                                <a href="{{ route('trades.show', $request->trade->id) }}"
                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                    Review
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        @endif

    </div>
</div>

@endsection
