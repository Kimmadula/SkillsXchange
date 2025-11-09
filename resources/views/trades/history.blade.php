@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5">
    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 fw-bold text-dark mb-2">Trade History</h1>
                <p class="text-muted">View your completed skill exchange sessions</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-1">Total Completed</p>
                                <h3 class="fw-bold mb-0">{{ $stats['total_completed'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
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

            <div class="col-md-4">
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
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Quick Filters</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('trades.history', ['range' => '1w']) }}"
                               class="btn btn-sm btn-outline-primary {{ request('range') === '1w' ? 'active' : '' }}">
                                Last 7 days
                            </a>
                            <a href="{{ route('trades.history', ['range' => '3m']) }}"
                               class="btn btn-sm btn-outline-primary {{ request('range') === '3m' ? 'active' : '' }}">
                                Last 3 months
                            </a>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <form method="GET" action="{{ route('trades.history') }}" class="row g-2" id="dateFilterForm">
                            <div class="col-md-4">
                                <label class="form-label small text-muted">From Date</label>
                                <input type="date" name="from_date" class="form-control form-control-sm"
                                       value="{{ request('from_date') }}" id="fromDateInput">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">To Date</label>
                                <input type="date" name="to_date" class="form-control form-control-sm"
                                       value="{{ request('to_date') }}" id="toDateInput">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill">Filter</button>
                                    <a href="{{ route('trades.history') }}" class="btn btn-outline-secondary btn-sm" title="Clear all filters">Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trade History List -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if($trades->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Skill Exchange</th>
                                    <th>Partner</th>
                                    <th>Session Type</th>
                                    <th>Completed Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trades as $trade)
                                    @php
                                        $isOwner = $trade->user_id === auth()->id();
                                        $partner = $isOwner
                                            ? ($trade->requests->first()?->requester ?? null)
                                            : $trade->user;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong class="text-primary">{{ $trade->offeringSkill->name ?? 'N/A' }}</strong>
                                                    <i class="fas fa-exchange-alt mx-2 text-muted"></i>
                                                    <strong class="text-success">{{ $trade->lookingSkill->name ?? 'N/A' }}</strong>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                @if($isOwner)
                                                    You offered <strong>{{ $trade->offeringSkill->name }}</strong> and learned <strong>{{ $trade->lookingSkill->name }}</strong>
                                                @else
                                                    You learned <strong>{{ $trade->offeringSkill->name }}</strong> and offered <strong>{{ $trade->lookingSkill->name }}</strong>
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @if($partner)
                                                <div class="d-flex align-items-center">
                                                    @if($partner->photo)
                                                        <img src="{{ asset('storage/' . $partner->photo) }}"
                                                             alt="{{ $partner->name }}"
                                                             class="rounded-circle me-2"
                                                             style="width: 32px; height: 32px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle me-2 bg-secondary d-flex align-items-center justify-content-center text-white"
                                                             style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                            {{ strtoupper(substr($partner->firstname, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-semibold">{{ $partner->name }}</div>
                                                        <small class="text-muted">@{{ $partner->username }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-capitalize">{{ $trade->session_type ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <div>{{ $trade->updated_at->format('M d, Y') }}</div>
                                            <small class="text-muted">{{ $trade->updated_at->format('g:i A') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('trades.show', $trade) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $trades->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No completed trades found</h5>
                        <p class="text-muted">Your completed skill exchange sessions will appear here.</p>
                        <a href="{{ route('trades.create') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-2"></i>Create New Trade
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

