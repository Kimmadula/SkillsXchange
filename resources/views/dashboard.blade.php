@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5 dark-theme">
    <div class="container">
        <!-- Welcome Section -->
        <div class="mb-4 mb-md-5">
            <h1 class="h2 fw-bold text-gradient mb-2">Welcome back, {{ auth()->user()->firstname }}!</h1>
            <p class="text-muted">Here's what's happening with your skill trades today.</p>
        </div>

        @if(auth()->user()->role === 'admin')
        <!-- Admin Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card fade-in">
                <div class="stat-icon stat-icon--primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value">{{ isset($stats) ? $stats['totalUsers'] : 0 }}</div>
                </div>
            </div>

            <div class="stat-card fade-in">
                <div class="stat-icon stat-icon--success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Verified Users</div>
                    <div class="stat-value">{{ isset($stats) ? $stats['verifiedUsers'] : 0 }}</div>
                </div>
            </div>

            <div class="stat-card fade-in">
                <div class="stat-icon stat-icon--warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Pending Users</div>
                    <div class="stat-value">{{ isset($stats) ? $stats['pendingUsers'] : 0 }}</div>
                </div>
            </div>

            <div class="stat-card fade-in">
                <div class="stat-icon stat-icon--info">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Trades</div>
                    <div class="stat-value">{{ isset($stats) ? $stats['totalTrades'] : 0 }}</div>
                </div>
            </div>
        </div>
        @else
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

            <div class="stat-card fade-in">
                <div class="stat-icon stat-icon--primary">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Ongoing Sessions</div>
                    <div class="stat-value">{{ isset($userStats) ? $userStats['ongoingSessions'] : 0 }}</div>
                </div>
            </div>

            <div class="stat-card fade-in">
                <div class="stat-icon stat-icon--warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-value">{{ isset($userStats) ? $userStats['pendingRequests'] : 0 }}</div>
                </div>
            </div>

            <div class="stat-card fade-in">
                <div class="stat-icon stat-icon--danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Declined Requests</div>
                    <div class="stat-value">{{ isset($userStats) ? $userStats['declinedRequests'] : 0 }}</div>
                </div>
            </div>
        </div>
        @endif

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
                            <h6 class="mb-1">View Profile</h6>
                            <small>Manage your skills and info</small>
                        </div>
                    </div>
                </a>
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
                                <a href="{{ route('chat.session', $session->id) }}"
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

        <!-- Admin Section (if admin) -->
        @if(auth()->user()->role === 'admin')
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Admin Panel</h2>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-md font-medium text-gray-700">Pending Users ({{ isset($pendingUsers) ?
                        $pendingUsers->count() : 0 }})</h3>
                    <a href="{{ route('admin.skills.index') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm">
                        Manage Skills
                    </a>
                </div>
                @if(isset($pendingUsers) && $pendingUsers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Name</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Email</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Skill</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pendingUsers->take(3) as $user)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    {{ $user->firstname }} {{ $user->lastname }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $user->email }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ optional($user->skill)->name ?? '—' }}</td>
                                <td class="px-4 py-2 whitespace-nowrap space-x-2">
                                    <a href="{{ route('admin.user.show', $user->id) }}"
                                        class="px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600 transition">
                                        View
                                    </a>
                                    <form action="{{ route('admin.approve', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600 transition">
                                            Approve
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($pendingUsers->count() > 3)
                    <div class="mt-4 text-center">
                        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">View all {{ $pendingUsers->count()
                            }} pending users</a>
                    </div>
                    @endif
                </div>
                @else
                <p class="text-gray-500 text-sm">No pending users at the moment.</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection