<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="{{ route('dashboard') }}">
            <x-application-logo class="me-2" />
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                @if(auth()->user()->role !== 'admin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.create') ? 'active' : '' }}"
                        href="{{ route('trades.create') }}">
                        <i class="fas fa-plus me-1"></i>Post Trade
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.matches') ? 'active' : '' }}"
                        href="{{ route('trades.matches') }}">
                        <i class="fas fa-search me-1"></i>Matches
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.requests') ? 'active' : '' }}"
                        href="{{ route('trades.requests') }}">
                        <i class="fas fa-handshake me-1"></i>Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trades.ongoing') ? 'active' : '' }}"
                        href="{{ route('trades.ongoing') }}">
                        <i class="fas fa-clock me-1"></i>Ongoing
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}"
                        href="{{ route('tasks.index') }}">
                        <i class="fas fa-tasks me-1"></i>Tasks
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('skills.*') ? 'active' : '' }}"
                       href="#" id="skillsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-graduation-cap me-1"></i>Skills
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('skills.index') }}">
                            <i class="fas fa-list me-2"></i>Browse Skills
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('skills.history') }}">
                            <i class="fas fa-history me-2"></i>My Skill History
                        </a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#buyTokensModal">
                        <i class="fas fa-coins me-1"></i>Buy Tokens
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tokens.history') ? 'active' : '' }}"
                       href="{{ route('tokens.history') }}">
                        <i class="fas fa-history me-1"></i>Token History
                    </a>
                </li>
                <li class="nav-item position-relative">
                    <a class="nav-link {{ request()->routeIs('trades.notifications') ? 'active' : '' }}"
                        href="{{ route('trades.notifications') }}">
                        <i class="fas fa-bell me-1"></i>Notifications
                        @php
                        $unreadCount = App\Http\Controllers\TradeController::getUnreadNotificationCount(Auth::id());
                        @endphp
                        @if($unreadCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                        @endif
                    </a>
                </li>
                @endif
            </ul>

            <!-- User Dropdown -->
            <div class="navbar-nav">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                        data-bs-toggle="dropdown">
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
                        <div class="d-none d-lg-block">
                            <div class="fw-semibold">{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</div>
                            <small class="text-muted">{{ Auth::user()->email }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
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
        </div>
    </div>
</nav>

<!-- Buy Tokens Modal -->
<div class="modal fade" id="buyTokensModal" tabindex="-1" aria-labelledby="buyTokensModalLabel" aria-hidden="true">
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

                    <!-- Quantity Selection -->
                    <div class="mb-3">
                        <label for="tokenQuantity" class="form-label">Number of Tokens</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" id="decreaseQuantity">-</button>
                            <input type="number" class="form-control text-center" id="tokenQuantity" name="quantity" value="1" min="1" max="100" required>
                            <button type="button" class="btn btn-outline-secondary" id="increaseQuantity">+</button>
                        </div>
                        <div class="form-text">Minimum: 1 token, Maximum: 100 tokens</div>
                    </div>

                    <!-- Price Display -->
                    <div class="mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Tokens:</strong> <span id="displayQuantity">1</span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <strong>Total: ₱<span id="totalPrice">5.00</span></strong>
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

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
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

    const TOKEN_PRICE = 5.00; // 1 token = 5 pesos

    function updateDisplay() {
        const quantity = parseInt(quantityInput.value) || 1;
        const total = quantity * TOKEN_PRICE;

        displayQuantity.textContent = quantity;
        totalPrice.textContent = total.toFixed(2);
    }

    function updateQuantity(change) {
        const currentValue = parseInt(quantityInput.value) || 1;
        const newValue = Math.max(1, Math.min(100, currentValue + change));
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

        if (quantity < 1 || quantity > 100) {
            e.preventDefault();
            alert('Please enter a valid quantity (1-100 tokens)');
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
</script>
