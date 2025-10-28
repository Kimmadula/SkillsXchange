@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 fw-bold text-dark mb-2">My Skill History</h1>
                <p class="text-muted">Track your skill acquisition journey and progress</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <a href="{{ route('skills.history.export') }}" class="btn btn-outline-primary">
                    <i class="fas fa-download me-2"></i>Export CSV
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-graduation-cap text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">Total Skills</p>
                                <h3 class="fw-bold mb-0">{{ $stats['total_skills'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-calendar-month text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">This Month</p>
                                <h3 class="fw-bold mb-0">{{ $stats['this_month'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-calendar-year text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">This Year</p>
                                <h3 class="fw-bold mb-0">{{ $stats['this_year'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-fire text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">Recent (7 days)</p>
                                <h3 class="fw-bold mb-0">{{ $stats['recent_acquisitions'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Acquisitions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Recent Acquisitions</h5>

                        @forelse($recentAcquisitions as $recent)
                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-{{ $recent->method_color }} rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 32px; height: 32px;">
                                    <i class="{{ $recent->method_icon }} text-white" style="font-size: 0.75rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $recent->skill->name }}</h6>
                                <small class="text-muted d-block">{{ $recent->method_display }}</small>
                                <small class="text-muted">{{ $recent->acquired_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                            <p class="mb-0">No recent acquisitions</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Acquisition Methods -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Acquisition Methods</h5>

                        @if(!empty($stats['by_method']))
                            @foreach($stats['by_method'] as $method => $count)
                            @php
                                $methodConfig = [
                                    'task_completion' => ['icon' => 'fas fa-tasks', 'color' => 'success', 'label' => 'Task Completion'],
                                    'manual_add' => ['icon' => 'fas fa-plus-circle', 'color' => 'primary', 'label' => 'Manual Addition'],
                                    'trade_completion' => ['icon' => 'fas fa-handshake', 'color' => 'info', 'label' => 'Trade Completion'],
                                    'verification' => ['icon' => 'fas fa-check-circle', 'color' => 'warning', 'label' => 'Verification']
                                ];
                                $config = $methodConfig[$method] ?? ['icon' => 'fas fa-question', 'color' => 'secondary', 'label' => ucfirst($method)];
                            @endphp
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="{{ $config['icon'] }} text-{{ $config['color'] }} me-2"></i>
                                    <span>{{ $config['label'] }}</span>
                                </div>
                                <span class="badge bg-{{ $config['color'] }}">{{ $count }}</span>
                            </div>
                            @endforeach
                        @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-chart-pie fa-2x mb-2"></i>
                            <p class="mb-0">No data available</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Full History -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Complete History</h5>

                        @forelse($skillHistory as $history)
                        <div class="d-flex align-items-start mb-4 pb-4 border-bottom">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-{{ $history->method_color }} rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <i class="{{ $history->method_icon }} text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">{{ $history->skill->name }}</h6>
                                        <span class="badge bg-light text-dark">{{ $history->skill->category }}</span>
                                    </div>
                                    <small class="text-muted">{{ $history->acquired_at->format('M j, Y g:i A') }}</small>
                                </div>

                                <div class="mb-2">
                                    <span class="badge bg-{{ $history->method_color }}">{{ $history->method_display }}</span>
                                    @if($history->score_achieved)
                                    <span class="badge bg-info ms-1">Score: {{ $history->score_achieved }}%</span>
                                    @endif
                                </div>

                                @if($history->task)
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-tasks me-1"></i>
                                        Task: {{ $history->task->title }}
                                    </small>
                                </div>
                                @endif

                                @if($history->trade)
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-handshake me-1"></i>
                                        Trade: {{ $history->trade->offeringSkill->name }} ↔ {{ $history->trade->lookingSkill->name }}
                                    </small>
                                </div>
                                @endif

                                @if($history->notes)
                                <div class="mt-2">
                                    <small class="text-muted">{{ $history->notes }}</small>
                                </div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                            <h5>No Skill History Yet</h5>
                            <p class="mb-0">Start completing tasks or trades to build your skill history!</p>
                        </div>
                        @endforelse

                        <!-- Pagination -->
                        @if($skillHistory->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $skillHistory->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
