<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Error Messages -->
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" id="resetPasswordForm">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email', $request->email) }}" required
                autocomplete="username" placeholder="Enter your email address" readonly style="background-color: #e9ecef; cursor: not-allowed;" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- New Password -->
        <div class="form-group">
            <label for="password" class="form-label">New Password</label>
            <input id="password" class="form-input" type="password" name="password" required autofocus
                autocomplete="new-password" placeholder="Enter your new password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm New Password -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required
                autocomplete="new-password" placeholder="Confirm your new password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="form-footer">
            <div class="form-footer-right" style="width: 100%; justify-content: center;">
                <button type="submit" class="btn-primary">
                    {{ __('RESET PASSWORD') }}
                </button>
            </div>
        </div>
    </form>


    <script>
        // Prevent double form submission
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }

            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Resetting...';

            // Re-enable after 5 seconds as fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'RESET PASSWORD';
            }, 5000);
        });

    </script>
</x-guest-layout>
