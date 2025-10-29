<style>
.sx-brand-banner {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-bottom: 1px solid #e2e8f0;
  padding: 0.75rem 0;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000; /* Above sidebar */
  transform: translateY(0);
  transition: transform 0.3s ease;
}

.sx-brand-banner.hidden {
  transform: translateY(-100%);
}

.sx-brand-banner .banner-top {
  min-height: 60px;
}

.sx-brand-banner .brand {
  color: #1f2937;
}

.sx-brand-banner .brand .fw-semibold {
  color: #6366f1;
  font-size: 1.1rem;
}

.sx-brand-banner .brand small {
  color: #4b5563;
  font-size: 0.8rem;
}

/* Sidebar Styles */
.sidebar {
  position: fixed;
  top: 0; /* Start from very top */
  left: 0;
  height: 100vh; /* Full viewport height */
  width: 250px;
  background: #ffffff;
  border-right: 1px solid #e2e8f0;
  z-index: 999; /* Below banner but above content */
  overflow-y: auto;
  padding-top: 100px; /* Push content below banner */
}

.sidebar .nav-link {
  color: #374151;
  padding: 0.75rem 1rem;
  border-radius: 0.375rem;
  margin: 0.25rem 0.5rem;
  transition: all 0.2s ease;
}

.sidebar .nav-link:hover {
  background-color: #f3f4f6;
  color: #6366f1;
}

.sidebar .nav-link.active {
  color: #6366f1;
  font-weight: 600;
  background-color: transparent;
}

.sidebar .nav-item {
  margin-bottom: 0.25rem;
}

.sidebar .dropdown-menu {
  position: static;
  float: none;
  width: auto;
  margin-top: 0;
  background-color: transparent;
  border: 0;
  box-shadow: none;
}

.sidebar .dropdown-item {
  padding: 0.5rem 1rem;
  color: #6b7280;
  margin: 0.125rem 0.5rem;
  border-radius: 0.25rem;
}

.sidebar .dropdown-item:hover {
  background-color: #f3f4f6;
  color: #6366f1;
}

/* Main content adjustment */
.main-content {
  margin-left: 250px;
  padding-top: 100px; /* Account for fixed banner height */
  transition: margin-left 0.3s ease;
}

/* Sidebar hidden state */
.sidebar.hidden {
  transform: translateX(-100%);
}

.main-content.sidebar-hidden {
  margin-left: 0;
}

/* Sidebar Toggle Button */
.sidebar-toggle-btn {
  position: fixed;
  top: 50%;
  left: 250px;
  transform: translateY(-50%);
  z-index: 1001;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0 8px 8px 0;
  padding: 12px 8px;
  box-shadow: 2px 0 4px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}

.sidebar-toggle-btn:hover {
  background: #f8fafc;
  border-color: #6366f1;
}

.sidebar-toggle-btn i {
  color: #6366f1;
  font-size: 14px;
  transition: transform 0.3s ease;
}

/* Button positioning handled by JavaScript */

/* Draggable Mobile Toggle Button */
.draggable-toggle {
  position: fixed;
  top: 80px; /* Right at banner bottom edge */
  left: 20px;
  z-index: 1001;
  cursor: move;
  user-select: none;
}

.mobile-sidebar-toggle {
  background: rgba(255, 255, 255, 0.95);
  color: #374151;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transition: all 0.3s ease;
  cursor: pointer;
  backdrop-filter: blur(10px);
}

.mobile-sidebar-toggle:hover {
  background: rgba(255, 255, 255, 1);
  border-color: #6366f1;
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(0,0,0,0.2);
}

.mobile-sidebar-toggle i {
  font-size: 18px;
  color: #6366f1;
}

.mobile-sidebar-toggle:active {
  transform: scale(0.95);
}

/* Mobile responsive */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }

  .sidebar.show {
    transform: translateX(0);
  }

  .main-content {
    margin-left: 0;
  }

  .sidebar-toggle {
    display: block;
  }
}

@media (min-width: 769px) {
  .sidebar-toggle {
    display: none;
  }
}

/* Notification Dropdown Styles */
.notification-dropdown {
  border: 1px solid #e5e7eb;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  padding: 0;
}

.notification-dropdown .dropdown-header {
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
  padding: 12px 16px;
  font-weight: 600;
}

.notification-dropdown .dropdown-item-text {
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.2s ease;
}

.notification-dropdown .dropdown-item-text:hover {
  background-color: #f8fafc;
}

.notification-dropdown .dropdown-item-text:last-child {
  border-bottom: none;
}

.notification-dropdown .dropdown-item-text[href] {
  cursor: pointer;
}

.notification-dropdown .dropdown-item-text[href]:hover {
  background-color: #f0f9ff;
  transform: translateX(2px);
}

.notification-dropdown .bg-light {
  background-color: #fef3c7 !important;
}

/* Theme-specific unread backgrounds */
.notification-dropdown .dropdown-item-text[href].bg-light {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
}

.notification-dropdown .dropdown-item {
  padding: 8px 16px;
  font-size: 0.875rem;
}

.notification-dropdown .dropdown-item:hover {
  background-color: #f8fafc;
}
</style>

<!-- Banner -->
<div class="sx-brand-banner">
    <div class="container-fluid px-3 d-flex align-items-center banner-top">
        <div class="d-flex align-items-center brand">
            <img src="{{ asset('logo.png') }}" alt="SkillsXchange Logo" style="height: 44px;" class="me-2">
            <div class="d-none d-md-block">
                <div class="fw-semibold">SkillsXchange</div>
                <small class="opacity-75">Trade, Learn, and Grow Together</small>
            </div>
        </div>
        @auth
        <div class="ms-auto d-flex align-items-center gap-3">
            <!-- Current balance with buy token modal trigger -->
            <div class="d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#buyTokensModal" style="cursor: pointer;">
                <i class="fas fa-coins text-warning"></i>
                <small class="text-muted fw-semibold">Tokens</small>
                <span class="badge bg-secondary-subtle text-dark border">{{ auth()->user()->token_balance ?? 0 }}</span>
            </div>

            <!-- Notifications Dropdown -->
            <div class="nav-item dropdown position-relative">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" style="padding: 0.25rem 0.5rem;">
                    <i class="fas fa-bell text-muted"></i>
                        @php
                        $unreadCount = App\Http\Controllers\TradeController::getUnreadNotificationCount(Auth::id());
                        $annUnread = App\Http\Controllers\DashboardController::getUnreadAnnouncementCount(Auth::id());
                        $totalUnread = $unreadCount + $annUnread;
                        @endphp
                        @if($totalUnread > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            {{ $totalUnread > 99 ? '99+' : $totalUnread }}
                        </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 400px; max-height: 500px; overflow-y: auto;">
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Notifications</h6>
                        @if($totalUnread > 0)
                        <small class="text-muted">{{ $totalUnread }} unread</small>
                        @endif
                    </div>
                    <div class="dropdown-divider"></div>

                    <!-- Announcements Section -->
                    <div class="px-3 py-2" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-bottom: 1px solid #e2e8f0;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-bullhorn" style="color: #6366f1;"></i>
                                <span class="fw-semibold" style="color: #1f2937;">Announcements</span>
                            </div>
                            @php
                            $announcementsCount = App\Models\Announcement::active()
                                ->audienceForUser(Auth::user())
                                ->get()
                                ->reject(function($a) { return $a->isReadBy(Auth::user()); })
                                ->count();
                            @endphp
                            @if($announcementsCount > 0)
                            <span class="badge" style="background: #6366f1; color: white;">{{ $announcementsCount }}</span>
                            @endif
                        </div>
                    </div>

                    @php
                    $recentAnnouncements = App\Models\Announcement::active()
                        ->audienceForUser(Auth::user())
                        ->orderByDesc('created_at')
                        ->limit(3)
                        ->get();
                    @endphp

                    @forelse($recentAnnouncements as $announcement)
                    <a href="{{ route('announcements.index') }}" class="dropdown-item-text px-3 py-2 {{ !$announcement->isReadBy(Auth::user()) ? 'bg-light' : '' }}" style="text-decoration: none; color: inherit;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">{{ $announcement->title }}</div>
                                <div class="text-muted small">{{ Str::limit($announcement->content, 60) }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $announcement->created_at->diffForHumans() }}</div>
                            </div>
                            @if(!$announcement->isReadBy(Auth::user()))
                            <div class="ms-2">
                                <div class="rounded-circle" style="width: 8px; height: 8px; background: #6366f1;"></div>
                            </div>
                            @endif
                        </div>
                    </a>
                    @empty
                    <div class="dropdown-item-text px-3 py-2 text-center text-muted">
                        <small>No announcements</small>
                    </div>
                    @endforelse

                    <div class="dropdown-divider"></div>
                    <a href="{{ route('announcements.index') }}" class="dropdown-item text-center text-primary">
                        <small>View all announcements</small>
                    </a>

                    <div class="dropdown-divider"></div>

                    <!-- Trade Notifications Section -->
                    <div class="px-3 py-2" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-bottom: 1px solid #bbf7d0;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-exchange-alt" style="color: #16a34a;"></i>
                            <span class="fw-semibold" style="color: #1f2937;">Trade Updates</span>
                        </div>
                    </div>

                    @php
                    $recentNotifications = DB::table('user_notifications')
                        ->where('user_id', Auth::id())
                        ->orderByDesc('id')
                        ->limit(5)
                        ->get()
                        ->map(function($notification) {
                            $notification->data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
                            return $notification;
                        });
                    @endphp

                    @forelse($recentNotifications as $n)
                    @php
                        $clickUrl = '';
                        if($n->type === 'trade_request') {
                            $clickUrl = route('trades.requests');
                        } elseif($n->type === 'trade_response') {
                            $clickUrl = isset($n->data['status']) && $n->data['status'] === 'accepted' ? route('trades.ongoing') : route('trades.matches');
                        } elseif($n->type === 'match_found') {
                            $clickUrl = route('trades.matches');
                        } else {
                            $clickUrl = route('trades.notifications');
                        }
                    @endphp
                    <a href="{{ $clickUrl }}" class="dropdown-item-text px-3 py-2 {{ !$n->read ? 'bg-light' : '' }}" style="text-decoration: none; color: inherit;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                @if($n->type === 'trade_request')
                                <div class="fw-semibold text-dark">🔔 New Trade Request</div>
                                <div class="text-muted small">
                                    @php $name = $n->data['requester_name'] ?? 'Unknown User'; @endphp
                                    <strong>{{ $name }}</strong> wants to trade with you
                                </div>
                                @elseif($n->type === 'trade_response')
                                <div class="fw-semibold text-dark">
                                    @if(isset($n->data['status']) && $n->data['status'] === 'accepted')
                                    ✅ Trade Accepted
                                    @else
                                    ❌ Trade Declined
                                    @endif
                                </div>
                                <div class="text-muted small">
                                    @php $owner = $n->data['trade_owner_name'] ?? 'Unknown User'; @endphp
                                    <strong>{{ $owner }}</strong>
                                    @if(isset($n->data['status']) && $n->data['status'] === 'accepted')
                                    accepted your trade request
                                    @else
                                    declined your trade request
                                    @endif
                                </div>
                                @elseif($n->type === 'match_found')
                                <div class="fw-semibold text-dark">🎯 New Match Found</div>
                                <div class="text-muted small">
                                    @php
                                    $partner = $n->data['partner_username'] ?? ($n->data['partner_name'] ?? 'a user');
                                    $score = isset($n->data['compatibility_score']) ? (int)$n->data['compatibility_score'] : null;
                                    @endphp
                                    @if($score !== null)
                                    A {{ $score }}% compatible trade with <strong>{{ $partner }}</strong>
                                    @else
                                    A compatible trade with <strong>{{ $partner }}</strong>
                                    @endif
                                </div>
                                @else
                                <div class="fw-semibold text-dark">🔔 Notification</div>
                                <div class="text-muted small">You have a new update</div>
                                @endif
                                <div class="text-muted" style="font-size: 0.75rem;">{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}</div>
                            </div>
                            @if(!$n->read)
                            <div class="ms-2">
                                <div class="rounded-circle" style="width: 8px; height: 8px; background: #16a34a;"></div>
                            </div>
                @endif
                        </div>
                    </a>
                    @empty
                    <div class="dropdown-item-text px-3 py-2 text-center text-muted">
                        <small>No trade notifications</small>
                    </div>
                    @endforelse

                    <div class="dropdown-divider"></div>
                    <a href="{{ route('trades.notifications') }}" class="dropdown-item text-center text-primary">
                        <small>View all trade notifications</small>
                    </a>
                </div>
            </div>
            <!-- Profile dropdown at far right -->
            <div class="nav-item dropdown order-2">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="me-2">
                            @if(Auth::user()->photo && file_exists(storage_path('app/public/' . Auth::user()->photo)))
                                <img src="{{ asset('storage/' . Auth::user()->photo) }}" alt="Profile Photo"
                                     class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                            @else
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 32px; height: 32px;">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            @endif
                        </div>
                    <div class="d-none d-lg-block text-end">
                        <div class="fw-semibold text-dark">{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</div>
                            <small class="text-muted">{{ Auth::user()->email }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                    <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-2"></i>Log Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
        </div>
        @endauth
            </div>
        </div>

<!-- Sidebar Toggle Button -->
<button class="sidebar-toggle-btn d-none d-lg-block" onclick="toggleDesktopSidebar()" title="Toggle Sidebar">
    <i class="fas fa-angle-double-left"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Sidebar Logo -->
    <div class="sidebar-logo p-3 text-center border-bottom">
        <img src="{{ asset('logo.png') }}" alt="SkillsXchange Logo" style="height: 40px;" class="mb-2">
        <div class="fw-semibold text-primary">SkillsXchange</div>
        <small class="text-muted">Trade, Learn, and Grow Together</small>
    </div>

    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                href="{{ route('dashboard') }}">
                Dashboard
            </a>
        </li>
        @if(auth()->user()->role !== 'admin')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('trades.create') ? 'active' : '' }}"
                href="{{ route('trades.create') }}">
                Post Trade
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('trades.matches') ? 'active' : '' }}"
                href="{{ route('trades.matches') }}">
                Matches
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('trades.requests') ? 'active' : '' }}"
                href="{{ route('trades.requests') }}">
                Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('trades.ongoing') ? 'active' : '' }}"
                href="{{ route('trades.ongoing') }}">
                Ongoing
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}"
                href="{{ route('tasks.index') }}">
                Tasks
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle {{ request()->routeIs('skills.*') ? 'active' : '' }}"
               href="#" id="skillsDropdown" role="button" data-bs-toggle="dropdown">
                Skills
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('skills.index') }}">
                    Browse Skills
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('skills.history') }}">
                    My Skill History
                </a></li>
            </ul>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('tokens.history') ? 'active' : '' }}"
               href="{{ route('tokens.history') }}">
                Token History
            </a>
        </li>
        @endif
    </ul>
</div>

<!-- Draggable Mobile Sidebar Toggle Button -->
<div class="draggable-toggle d-lg-none" id="draggableToggle">
    <button class="mobile-sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Buy Tokens Modal -->
<div class="modal fade" id="buyTokensModal" tabindex="-1" aria-labelledby="buyTokensModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buyTokensModalLabel">
                    <i class="fas fa-coins me-2"></i>Buy Tokens
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="buyTokensForm" method="POST" action="{{ route('tokens.purchase') }}">
                @csrf
                <div class="modal-body">
                    <!-- Test Mode Notice -->
                    <div class="alert alert-warning d-flex align-items-center mb-3">
                        <i class="fas fa-flask me-2"></i>
                        <div>
                            <strong>Test Mode:</strong> This is a sandbox environment. No real money will be charged.
                        </div>
                    </div>

                    <!-- Current Balance Display -->
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="fas fa-wallet me-2"></i>
                        <div>
                            <strong>Current Balance:</strong> {{ auth()->user()->token_balance ?? 0 }} tokens
                        </div>
                    </div>

                    @php
                        $tokenPrice = \App\Models\TradeFeeSetting::getFeeAmount('token_price') ?: 5;
                        $minQty = max(1, (int) ceil(100 / max($tokenPrice, 0.01))); // PayMongo min ₱100
                        $maxQty = 100;
                        $minAmt = $minQty * $tokenPrice;
                    @endphp

                    <!-- Quantity Selection -->
                    <div class="mb-3">
                        <label for="tokenQuantity" class="form-label">Number of Tokens</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" id="decreaseQuantity">-</button>
                            <input type="number" class="form-control text-center" id="tokenQuantity" name="quantity" value="{{ $minQty }}" min="{{ $minQty }}" max="100" required>
                            <button type="button" class="btn btn-outline-secondary" id="increaseQuantity">+</button>
                        </div>
                        <div class="form-text">Minimum payment: ₱100.00 ({{ $minQty }} tokens at ₱{{ number_format($tokenPrice, 2) }} each). Maximum: 100 tokens</div>
                    </div>

                    <!-- Price Display -->
                    <div class="mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Tokens:</strong> <span id="displayQuantity">{{ $minQty }}</span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <strong>Total: ₱<span id="totalPrice">{{ number_format($minAmt, 2) }}</span></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Terms and Conditions -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="purchaseBtn">
                        <i class="fas fa-credit-card me-2"></i>Pay now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Prevent ARIA warning by blurring focus before opening any modal (handles nested modals as well)
document.addEventListener('show.bs.modal', function () {
    if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
    }
});

// Also ensure focus is moved to body when a modal is hidden
document.addEventListener('hide.bs.modal', function () {
    if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
    }
    setTimeout(function () { document.body.focus(); }, 0);
});
document.addEventListener('hidden.bs.modal', function () {
    setTimeout(function () { document.body.focus(); }, 0);
});
</script>
<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Token Purchase Terms</h6>
                <ul>
                    <li>Tokens are non-refundable once purchased</li>
                    <li>Tokens do not expire and can be used indefinitely</li>
                    <li>All transactions are final and cannot be reversed</li>
                    <li>Tokens can be used for premium features on the platform</li>
                    <li>Prices are subject to change without notice</li>
                </ul>

                <h6>Payment Terms</h6>
                <ul>
                    <li>Payments are processed securely through PayMongo</li>
                    <li>GCash payments are processed in real-time</li>
                    <li>Failed payments will not result in token charges</li>
                    <li>All prices are in Philippine Peso (₱)</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('tokenQuantity');
    const displayQuantity = document.getElementById('displayQuantity');
    const totalPrice = document.getElementById('totalPrice');
    const decreaseBtn = document.getElementById('decreaseQuantity');
    const increaseBtn = document.getElementById('increaseQuantity');
    const purchaseBtn = document.getElementById('purchaseBtn');
    const form = document.getElementById('buyTokensForm');

    const TOKEN_PRICE = {{ (float) $tokenPrice }}; // price per token in PHP pesos

    function updateDisplay() {
        const quantity = parseInt(quantityInput.value) || 1;
        const total = quantity * TOKEN_PRICE;

        displayQuantity.textContent = quantity;
        totalPrice.textContent = total.toFixed(2);
    }

    function updateQuantity(change) {
        const currentValue = parseInt(quantityInput.value) || {{ $minQty }};
        const newValue = Math.max({{ $minQty }}, Math.min({{ $maxQty }}, currentValue + change));
        quantityInput.value = newValue;
        updateDisplay();
    }

    decreaseBtn.addEventListener('click', () => updateQuantity(-1));
    increaseBtn.addEventListener('click', () => updateQuantity(1));

    quantityInput.addEventListener('input', updateDisplay);

    // Form submission
    form.addEventListener('submit', function(e) {
        const quantity = parseInt(quantityInput.value);
        const total = quantity * TOKEN_PRICE;

        if (quantity < {{ $minQty }} || quantity > {{ $maxQty }}) {
            e.preventDefault();
            alert('Please enter a valid quantity ({{ $minQty }}-{{ $maxQty }} tokens). Minimum purchase is ₱100.00');
            return;
        }

        if (!document.getElementById('agreeTerms').checked) {
            e.preventDefault();
            alert('Please agree to the Terms and Conditions');
            return;
        }

        // Show loading state
        purchaseBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        purchaseBtn.disabled = true;

        // Add hidden field for total amount
        const totalInput = document.createElement('input');
        totalInput.type = 'hidden';
        totalInput.name = 'total_amount';
        totalInput.value = total;
        form.appendChild(totalInput);
    });

    // Initialize display
    updateDisplay();
});

// Mobile sidebar toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
}

// Close sidebar when clicking outside (mobile only)
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const draggableToggle = document.getElementById('draggableToggle');

    // Only apply to mobile devices
    if (window.innerWidth <= 768) {
        // Check if sidebar is open and click is outside sidebar and toggle button
        if (sidebar.classList.contains('show') &&
            !sidebar.contains(event.target) &&
            !draggableToggle.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    }
});

// Draggable toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const draggableToggle = document.getElementById('draggableToggle');
    let isDragging = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    let xOffset = 0;
    let yOffset = 0;

    // Touch events for mobile only
    draggableToggle.addEventListener('touchstart', dragStart, false);
    draggableToggle.addEventListener('touchend', dragEnd, false);
    draggableToggle.addEventListener('touchmove', drag, false);

    function dragStart(e) {
        initialX = e.touches[0].clientX - xOffset;
        initialY = e.touches[0].clientY - yOffset;

        if (e.target === draggableToggle || draggableToggle.contains(e.target)) {
            isDragging = true;
        }
    }

    function dragEnd(e) {
        initialX = currentX;
        initialY = currentY;
        isDragging = false;
    }

    function drag(e) {
        if (isDragging) {
            e.preventDefault();

            currentX = e.touches[0].clientX - initialX;
            currentY = e.touches[0].clientY - initialY;

            xOffset = currentX;
            yOffset = currentY;

            // Keep within viewport bounds (allow moving above banner)
            const maxX = window.innerWidth - draggableToggle.offsetWidth;
            const maxY = window.innerHeight - draggableToggle.offsetHeight;

            currentX = Math.max(-50, Math.min(currentX, maxX)); // Allow 50px above screen
            currentY = Math.max(-50, Math.min(currentY, maxY)); // Allow 50px above screen

            draggableToggle.style.transform = `translate3d(${currentX}px, ${currentY}px, 0)`;
        }
    }
});

// Desktop sidebar toggle function
function toggleDesktopSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    const toggleIcon = toggleBtn.querySelector('i');

    sidebar.classList.toggle('hidden');
    mainContent.classList.toggle('sidebar-hidden');

    // Update button position and icon
    if (sidebar.classList.contains('hidden')) {
        toggleBtn.style.left = '0';
        toggleIcon.style.transform = 'rotate(180deg)';
    } else {
        toggleBtn.style.left = '250px';
        toggleIcon.style.transform = 'rotate(0deg)';
    }
}

// Banner scroll behavior
let lastScrollTop = 0;
let banner = document.querySelector('.sx-brand-banner');
let sidebar = document.getElementById('sidebar');
let mainContent = document.querySelector('.main-content');

// Scroll behavior for both desktop and mobile
window.addEventListener('scroll', function() {
    // Check if elements exist before accessing their properties
    if (!banner) return;

    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    // Add some tolerance to prevent flickering
    const scrollThreshold = 50;
    const scrollDifference = Math.abs(scrollTop - lastScrollTop);

    // Only trigger if scroll difference is significant
    if (scrollDifference < 5) return;

    if (scrollTop > lastScrollTop && scrollTop > scrollThreshold) {
        // Scrolling down - hide banner
        banner.classList.add('hidden');
        if (sidebar) sidebar.style.paddingTop = '1rem';
        if (mainContent) mainContent.style.paddingTop = '1rem';
    } else if (scrollTop < lastScrollTop) {
        // Scrolling up - show banner
        banner.classList.remove('hidden');
        if (sidebar) sidebar.style.paddingTop = '100px';
        if (mainContent) mainContent.style.paddingTop = '100px';
    }

    lastScrollTop = scrollTop;
});

// Click outside to show banner
document.addEventListener('click', function(event) {
    // Check if click is outside interactive elements
    const interactiveElements = [
        'button', 'input', 'textarea', 'select', 'a',
        '.btn', '.form-control', '.dropdown-toggle',
        '.nav-link', '.dropdown-item', '.modal'
    ];

    let isInteractiveElement = false;

    // Check if clicked element or its parents are interactive
    let element = event.target;
    while (element && element !== document.body) {
        const tagName = element.tagName.toLowerCase();
        const className = element.className || '';

        if (interactiveElements.some(selector =>
            selector.startsWith('.') ? className.includes(selector.substring(1)) : tagName === selector
        )) {
            isInteractiveElement = true;
            break;
        }
        element = element.parentElement;
    }

    // If clicked outside interactive elements, show banner
    if (!isInteractiveElement && banner && banner.classList.contains('hidden')) {
        banner.classList.remove('hidden');
        if (sidebar) sidebar.style.paddingTop = '100px';
        if (mainContent) mainContent.style.paddingTop = '100px';
    }
});
</script>
