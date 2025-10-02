<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="{{ route('dashboard') }}">
            <x-application-logo class="me-2" />
            <span class="d-none d-sm-inline">SkillsXchange</span>
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
                        <li><a class="dropdown-item" href="{{ route('skills.create') }}">
                            <i class="fas fa-plus me-2"></i>Add New Skill
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('skills.history') }}">
                            <i class="fas fa-history me-2"></i>My Skill History
                        </a></li>
                    </ul>
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
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px;">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        </div>
                        <div class="d-none d-lg-block">
                            <div class="fw-semibold">{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</div>
                            <small class="text-muted">{{ Auth::user()->email }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="fas fa-user-edit me-2"></i>Profile
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