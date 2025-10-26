@if(auth()->check() && auth()->user()->isAccountRestricted())
    @php
        $suspension = auth()->user()->getCurrentSuspension();
        $ban = auth()->user()->getCurrentBan();
    @endphp
    
    @if($ban)
        <!-- Permanent Ban Banner -->
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 0; border-radius: 0; border-left: none; border-right: none;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-1 text-center">
                        <i class="fas fa-ban fa-2x"></i>
                    </div>
                    <div class="col-md-10">
                        <h5 class="alert-heading mb-1">
                            <i class="fas fa-exclamation-triangle me-2"></i>Account Permanently Banned
                        </h5>
                        <p class="mb-1">
                            <strong>Your account has been permanently banned.</strong>
                        </p>
                        <p class="mb-0">
                            <strong>Reason:</strong> {{ $ban->reason }}
                        </p>
                        @if($ban->admin_notes)
                            <p class="mb-0 mt-2">
                                <strong>Additional Details:</strong> {{ $ban->admin_notes }}
                            </p>
                        @endif
                    </div>
                    <div class="col-md-1 text-center">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @elseif($suspension && $suspension->isSuspensionActive())
        <!-- Suspension Banner -->
        <div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin: 0; border-radius: 0; border-left: none; border-right: none;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-1 text-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div class="col-md-10">
                        <h5 class="alert-heading mb-1">
                            <i class="fas fa-exclamation-triangle me-2"></i>Account Suspended
                        </h5>
                        <p class="mb-1">
                            <strong>Your account is currently suspended.</strong>
                        </p>
                        <p class="mb-1">
                            <strong>Reason:</strong> {{ $suspension->reason }}
                        </p>
                        @if($suspension->suspension_end)
                            <p class="mb-1">
                                <strong>Suspension ends:</strong> {{ $suspension->suspension_end->format('M d, Y \a\t g:i A') }}
                            </p>
                        @else
                            <p class="mb-1">
                                <strong>Duration:</strong> Indefinite suspension
                            </p>
                        @endif
                        @if($suspension->admin_notes)
                            <p class="mb-0 mt-2">
                                <strong>Additional Details:</strong> {{ $suspension->admin_notes }}
                            </p>
                        @endif
                    </div>
                    <div class="col-md-1 text-center">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
