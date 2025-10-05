<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    
    <!-- Session Expiration Message -->
    @if(request()->get('expired'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Session Expired!</strong> Your session has expired due to inactivity. Please log in again to continue.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    
    <!-- Error Messages -->
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Username or Email -->
        <div class="form-group">
            <label for="login" class="form-label">Username or Email</label>
            <input id="login" class="form-input" type="text" name="login" value="{{ old('login') }}" required autofocus
                autocomplete="username" placeholder="Enter your username or email" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input id="password" class="form-input" type="password" name="password" required
                autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="checkbox-group">
            <input id="remember_me" type="checkbox" name="remember">
            <label for="remember_me">{{ __('Remember me') }}</label>
        </div>

        <div class="form-footer">
            <div class="form-footer-left">
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
                @endif
            </div>

            <div class="form-footer-right">
                <a href="{{ url('/') }}" class="btn-secondary">
                    {{ __('Back') }}
                </a>
                <button type="submit" class="btn-primary">
                    {{ __('LOG IN') }}
                </button>
            </div>
        </div>
    </form>

    <!-- Firebase Authentication Option -->
    <div class="auth-divider">
        <span>or</span>
    </div>

    <div class="firebase-auth-section">
        <a href="{{ route('firebase.login') }}" class="btn-firebase">
            <i class="fas fa-fire me-2"></i>
            Sign in with Firebase
        </a>
        <p class="firebase-description">
            Use Firebase Authentication for enhanced security and social login options
        </p>
    </div>
</x-guest-layout>