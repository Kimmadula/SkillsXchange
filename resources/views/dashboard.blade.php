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
        <!-- Welcome Section (only for new and returning users, not for long-time users) -->
        @php
            $isFirstVisit = auth()->user()->created_at->isToday();
            $daysSinceJoined = auth()->user()->created_at->diffInDays(now());
            $isLongTimeUser = $daysSinceJoined >= 7; // Hide after 7 days
            
            // Dynamic subtitle for returning users based on activity
            $subtitle = "Here's what's happening with your skill trades today.";
            if (!$isFirstVisit && !$isLongTimeUser && isset($userStats)) {
                if ($userStats['pendingRequests'] > 0) {
                    $subtitle = "You have {$userStats['pendingRequests']} pending request(s) waiting for your response.";
                } elseif ($userStats['pendingRequestsToMe'] > 0) {
                    $subtitle = "You have {$userStats['pendingRequestsToMe']} new request(s) on your trades.";
                } elseif ($userStats['ongoingSessions'] > 0) {
                    $subtitle = "You have {$userStats['ongoingSessions']} active session(s). Keep up the great work!";
                } elseif ($userStats['completedSessions'] > 0) {
                    $subtitle = "You've completed {$userStats['completedSessions']} session(s). Well done!";
                }
            }
        @endphp
        
        @if(!$isLongTimeUser)
        <div class="mb-4 mb-md-5">
            @if($isFirstVisit)
                <h1 class="h2 fw-bold text-gradient mb-2">Welcome to SkillsXchange, {{ auth()->user()->firstname }}! <span class="badge" style="background: #10b981; color: white; font-size: 0.6rem; vertical-align: middle; margin-left: 8px;">New</span></h1>
                <p class="text-muted">Start by posting your first trade or browsing available matches.</p>
            @else
                <h1 class="h2 fw-bold text-gradient mb-2">Welcome back, {{ auth()->user()->firstname }}!</h1>
                <p class="text-muted">{{ $subtitle }}</p>
            @endif
        </div>
        @endif

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

        <!-- Active Announcements -->
        @if(isset($announcements) && $announcements->count() > 0)
        <div class="dashboard-card slide-up">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-bullhorn me-2" style="color: #6366f1;"></i>
                    <h5 class="mb-0 text-gradient">Announcements</h5>
                </div>
                <a href="{{ route('announcements.index') }}" class="text-decoration-none" style="font-size: 0.875rem; color: #6366f1;">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div style="display:grid; gap:12px;">
                @foreach($announcements->take(3) as $announcement)
                <div style="background:{{ $announcement->type === 'danger' ? '#fef2f2' : ($announcement->type === 'warning' ? '#fffbeb' : ($announcement->type === 'success' ? '#ecfdf5' : '#eff6ff')) }}; border:1px solid {{ $announcement->type === 'danger' ? '#fecaca' : ($announcement->type === 'warning' ? '#fde68a' : ($announcement->type === 'success' ? '#a7f3d0' : '#bfdbfe')) }}; border-radius:8px; padding:12px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex:1;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fas {{ $announcement->type === 'danger' ? 'fa-exclamation-circle' : ($announcement->type === 'warning' ? 'fa-exclamation-triangle' : ($announcement->type === 'success' ? 'fa-check-circle' : 'fa-info-circle')) }}" style="color:{{ $announcement->type === 'danger' ? '#dc2626' : ($announcement->type === 'warning' ? '#d97706' : ($announcement->type === 'success' ? '#059669' : '#2563eb')) }};"></i>
                                <div style="font-weight:600; color:#1f2937;">{{ $announcement->title }}</div>
                                @if($announcement->priority === 'urgent')
                                <span class="badge" style="background:#dc2626; color:white; font-size:0.7rem;">URGENT</span>
                                @elseif($announcement->priority === 'high')
                                <span class="badge" style="background:#d97706; color:white; font-size:0.7rem;">HIGH</span>
                                @endif
                            </div>
                            <div style="color:#4b5563; font-size:0.875rem; margin-bottom:8px;">
                                {{ Str::limit($announcement->message, 150) }}
                            </div>
                            <div style="font-size:0.75rem; color:#6b7280;">
                                {{ $announcement->created_at->diffForHumans() }}
                            </div>
                        </div>
                        @if(!$announcement->isReadBy(Auth::user()))
                        <div class="ms-2">
                            <div class="rounded-circle" style="width: 10px; height: 10px; background: #6366f1;"></div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @if($announcements->count() > 3)
            <div class="text-center mt-3">
                <a href="{{ route('announcements.index') }}" class="text-decoration-none" style="color: #6366f1; font-size: 0.875rem;">
                    View {{ $announcements->count() - 3 }} more announcement(s) <i class="fas fa-arrow-right ms-1"></i>
                </a>
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
        <div class="dashboard-card slide-up">
            <h2 class="h6 fw-bold text-gradient mb-3" style="font-size:0.95rem;">Completed Sessions</h2>
            <div style="display:grid; gap:8px;">
                @foreach($completedSessions->take(5) as $session)
                <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; background:#fafafa;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; color:#111827; font-size:0.875rem; margin-bottom:4px;">
                                {{ $session->offeringSkill->name }} ↔ {{ $session->lookingSkill->name }}
                            </div>
                            <div style="color:#6b7280; font-size:0.8rem; line-height:1.4;">
                                @if($session->user_id === auth()->id())
                                    Offered {{ $session->offeringSkill->name }} • Learned {{ $session->lookingSkill->name }}
                                @else
                                    Learned {{ $session->offeringSkill->name }} • Offered {{ $session->lookingSkill->name }}
                                @endif
                            </div>
                            <div style="color:#9ca3af; font-size:0.75rem; margin-top:4px;">
                                {{ $session->updated_at->format('M d, Y') }}
                            </div>
                        </div>
                        <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:12px; font-size:0.7rem; font-weight:500; background:#dcfce7; color:#166534; white-space:nowrap;">
                            Completed
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Ongoing Sessions -->
        @if(isset($ongoingSessions) && $ongoingSessions->count() > 0)
        <div class="dashboard-card slide-up">
            <h2 class="h6 fw-bold text-gradient mb-3" style="font-size:0.95rem;">Ongoing Sessions</h2>
            <div style="display:grid; gap:8px;">
                @foreach($ongoingSessions->take(5) as $session)
                <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; background:#fafafa;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; color:#111827; font-size:0.875rem; margin-bottom:4px;">
                                {{ $session->offeringSkill->name }} ↔ {{ $session->lookingSkill->name }}
                            </div>
                            <div style="color:#6b7280; font-size:0.8rem; line-height:1.4;">
                                @if($session->user_id === auth()->id())
                                    Offering {{ $session->offeringSkill->name }} • Learning {{ $session->lookingSkill->name }}
                                @else
                                    Learning {{ $session->offeringSkill->name }} • Offering {{ $session->lookingSkill->name }}
                                @endif
                            </div>
                            <div style="color:#9ca3af; font-size:0.75rem; margin-top:4px;">
                                Started {{ $session->start_date->format('M d, Y') }}
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                            <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:12px; font-size:0.7rem; font-weight:500; background:#dbeafe; color:#1e40af; white-space:nowrap;">
                                Active
                            </span>
                            <a href="{{ route('chat.show', $session->id) }}" style="color:#2563eb; font-size:0.8rem; text-decoration:none; font-weight:500; white-space:nowrap;">
                                Chat →
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Pending Requests -->
        @if(isset($pendingRequests) && $pendingRequests->count() > 0)
        <div class="dashboard-card slide-up">
            <h2 class="h6 fw-bold text-gradient mb-3" style="font-size:0.95rem;">Your Pending Requests</h2>
            <div style="display:grid; gap:8px;">
                @foreach($pendingRequests->take(5) as $request)
                <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; background:#fafafa;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; color:#111827; font-size:0.875rem; margin-bottom:4px;">
                                To: {{ $request->trade->user->firstname }} {{ $request->trade->user->lastname }}
                            </div>
                            <div style="color:#6b7280; font-size:0.8rem; line-height:1.4;">
                                Learn {{ $request->trade->offeringSkill->name }} • Offer {{ $request->trade->lookingSkill->name }}
                            </div>
                            @if($request->message)
                            <div style="color:#4b5563; font-size:0.75rem; font-style:italic; margin-top:4px; padding-left:8px; border-left:2px solid #e5e7eb;">
                                "{{ Str::limit($request->message, 60) }}"
                            </div>
                            @endif
                            <div style="color:#9ca3af; font-size:0.75rem; margin-top:4px;">
                                Sent {{ $request->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:12px; font-size:0.7rem; font-weight:500; background:#fef3c7; color:#92400e; white-space:nowrap;">
                            Pending
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Declined Requests -->
        @if(isset($declinedRequests) && $declinedRequests->count() > 0)
        <div class="dashboard-card slide-up">
            <h2 class="h6 fw-bold text-gradient mb-3" style="font-size:0.95rem;">Declined Requests</h2>
            <div style="display:grid; gap:8px;">
                @foreach($declinedRequests->take(5) as $request)
                <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; background:#fafafa;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; color:#111827; font-size:0.875rem; margin-bottom:4px;">
                                To: {{ $request->trade->user->firstname }} {{ $request->trade->user->lastname }}
                            </div>
                            <div style="color:#6b7280; font-size:0.8rem; line-height:1.4;">
                                Learn {{ $request->trade->offeringSkill->name }} • Offer {{ $request->trade->lookingSkill->name }}
                            </div>
                            <div style="color:#9ca3af; font-size:0.75rem; margin-top:4px;">
                                Declined {{ $request->responded_at->format('M d, Y') }}
                            </div>
                        </div>
                        <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:12px; font-size:0.7rem; font-weight:500; background:#fee2e2; color:#991b1b; white-space:nowrap;">
                            Declined
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Requests to Your Trades -->
        @if(isset($pendingRequestsToMe) && $pendingRequestsToMe->count() > 0)
        <div class="dashboard-card slide-up">
            <h2 class="h6 fw-bold text-gradient mb-3" style="font-size:0.95rem;">Requests to Your Trades</h2>
            <div style="display:grid; gap:8px;">
                @foreach($pendingRequestsToMe->take(5) as $request)
                <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; background:#fafafa;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; color:#111827; font-size:0.875rem; margin-bottom:4px;">
                                From: {{ $request->requester->firstname }} {{ $request->requester->lastname }}
                            </div>
                            <div style="color:#6b7280; font-size:0.8rem; line-height:1.4;">
                                Learn {{ $request->trade->offeringSkill->name }} • Offer {{ $request->trade->lookingSkill->name }}
                            </div>
                            @if($request->message)
                            <div style="color:#4b5563; font-size:0.75rem; font-style:italic; margin-top:4px; padding-left:8px; border-left:2px solid #e5e7eb;">
                                "{{ Str::limit($request->message, 60) }}"
                            </div>
                            @endif
                            <div style="color:#9ca3af; font-size:0.75rem; margin-top:4px;">
                                Received {{ $request->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                            <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:12px; font-size:0.7rem; font-weight:500; background:#fef3c7; color:#92400e; white-space:nowrap;">
                                Pending
                            </span>
                            <a href="{{ route('trades.show', $request->trade->id) }}" style="color:#2563eb; font-size:0.8rem; text-decoration:none; font-weight:500; white-space:nowrap;">
                                Review →
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endif

    </div>
</div>

@endsection
