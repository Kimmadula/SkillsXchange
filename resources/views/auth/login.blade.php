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

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf

        <!-- Username or Email -->
        <div class="form-group">
            <label for="login" class="form-label">Username or Email</label>
            <input id="login" class="form-input" type="text" name="login" value="{{ old('login') }}" required autofocus
                autocomplete="username" placeholder="Enter your username or email" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />

            <!-- Resend Verification Email Button -->
            @if($errors->has('login') && (str_contains(strtolower($errors->first('login')), 'verify') || str_contains(strtolower($errors->first('login')), 'verification')))
                @php
                    $loginValue = old('login');
                    $isEmail = filter_var($loginValue, FILTER_VALIDATE_EMAIL);
                @endphp
                @if($isEmail)
                <div class="mt-3">
                    <button type="button" id="resendVerificationBtn" class="btn btn-link p-0 text-decoration-none" style="color: #0d6efd; font-size: 0.875rem;">
                        <i class="fas fa-envelope me-1"></i> Resend verification email
                    </button>
                </div>
                @endif
            @endif
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

    <!-- Resend Verification Email Form (hidden, submitted via JavaScript) -->
    @if($errors->has('login') && (str_contains(strtolower($errors->first('login')), 'verify') || str_contains(strtolower($errors->first('login')), 'verification')))
        @php
            $loginValue = old('login');
            $isEmail = filter_var($loginValue, FILTER_VALIDATE_EMAIL);
        @endphp
        @if($isEmail)
        <form method="POST" action="{{ route('verification.resend') }}" id="resendVerificationForm" style="display: none;">
            @csrf
            <input type="hidden" name="email" value="{{ $loginValue }}">
        </form>
        @endif
    @endif

    <script>
        // Prevent double form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }

            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';

            // Re-enable after 5 seconds as fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'LOG IN';
            }, 5000);
        });

        // Handle resend verification email button click
        const resendBtn = document.getElementById('resendVerificationBtn');
        if (resendBtn) {
            resendBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const form = document.getElementById('resendVerificationForm');
                if (form) {
                    // Disable button and show loading state
                    resendBtn.disabled = true;
                    resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';

                    // Submit the form
                    form.submit();
                }
            });
        }

    </script>
</x-guest-layout>
