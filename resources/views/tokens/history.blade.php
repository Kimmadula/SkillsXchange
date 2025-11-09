@extends('layouts.app')

@section('content')
<div class="py-4 py-md-5 dark-theme">
    <div class="container">
        <!-- Header Section -->
        <div class="mb-4 mb-md-5">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 fw-bold text-gradient mb-2">
                        <i class="fas fa-history me-2"></i>Token History
                    </h1>
                    <p class="text-muted">View your token purchase and usage history.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Messages -->
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        <!-- Payment Status Messages -->
        @if(isset($message) && isset($messageType))
        <div class="alert alert-{{ $messageType === 'success' ? 'success' : 'danger' }} alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-{{ $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
            {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Current Balance Card -->
        <div class="dashboard-card dashboard-card--stats slide-up mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="h5 fw-bold text-gradient mb-1">Current Balance</h3>
                    <p class="text-muted mb-0">Your available tokens</p>
                </div>
                <div class="text-end">
                    <div class="h3 fw-bold text-primary mb-0">{{ auth()->user()->token_balance ?? 0 }}</div>
                    <small class="text-muted">tokens</small>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="dashboard-card dashboard-card--stats slide-up">
            <h2 class="h5 fw-bold text-gradient mb-4">Transaction History</h2>

            <!-- Filters -->
            <form method="GET" class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label for="from_date" class="form-label">From</label>
                    <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="to_date" class="form-label">To</label>
                    <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="form-control" required>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                    <a href="{{ route('tokens.history') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                </div>
            </form>

            <!-- Quick ranges -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="{{ route('tokens.history', ['range' => '1w']) }}" class="btn btn-sm btn-outline-primary {{ request('range') === '1w' ? 'active' : '' }}">
                    Last 7 days
                </a>
                <a href="{{ route('tokens.history', ['range' => '1m']) }}" class="btn btn-sm btn-outline-primary {{ request('range') === '1m' ? 'active' : '' }}">
                    Last 30 days
                </a>
            </div>

            @if($transactions->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                        @php
                            // Check if this is a premium subscription
                            $notes = $transaction->notes ?? '';
                            $isPremiumSubscription = false;
                            if (!empty($notes)) {
                                $notesData = json_decode($notes, true);
                                if (is_array($notesData) && isset($notesData['type']) && $notesData['type'] === 'premium_subscription') {
                                    $isPremiumSubscription = true;
                                } elseif (stripos($notes, 'premium') !== false && stripos($notes, 'subscription') !== false) {
                                    $isPremiumSubscription = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('M d, Y H:i') }}</td>
                            <td>
                                @if($isPremiumSubscription)
                                    <span class="badge" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff;">
                                        <i class="fas fa-crown me-1"></i>Premium Subscription
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        <i class="fas fa-coins me-1"></i>Token Purchase
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($isPremiumSubscription)
                                    <span class="text-muted">N/A</span>
                                @else
                                    {{ $transaction->quantity }} tokens
                                @endif
                            </td>
                            <td>₱{{ number_format($transaction->amount, 2) }}</td>
                            <td>
                                @switch($transaction->status)
                                    @case('completed')
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Completed
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Pending
                                        </span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Failed
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-ban me-1"></i>Cancelled
                                        </span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-receipt fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">No transactions yet</h5>
                <p class="text-muted">Your token purchase history will appear here.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buyTokensModal">
                    <i class="fas fa-coins me-2"></i>Buy Tokens
                </button>
            </div>
            @endif

            <!-- Pagination -->
            <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Showing {{ $transactions->count() }} of {{ $transactions->total() }} results</small>
                {{ $transactions->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card dashboard-card--stats slide-up mt-4">
            <div class="mb-2">
                <small class="text-muted">Click to buy tokens or subscribe to premium</small>
            </div>
            <div class="d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#buyTokensModal" style="cursor: pointer;">
                <i class="fas fa-coins text-warning"></i>
                <small class="text-muted fw-semibold">Tokens</small>
                <span class="badge bg-secondary-subtle text-dark border">{{ auth()->user()->token_balance ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
