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

    <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
        @csrf

        <!-- Email Address -->
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus
                autocomplete="email" placeholder="Enter your email address" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="form-footer">
            <div class="form-footer-left">
                <a href="{{ route('login') }}">
                    {{ __('Back to Login') }}
                </a>
            </div>


                <button type="submit" class="btn-primary">
                    {{ __('SEND RESET LINK') }}
                </button>
            </div>
        </div>
    </form>


    <script>
        // Prevent double form submission
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }

            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            // Re-enable after 5 seconds as fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'SEND RESET LINK';
            }, 5000);
        });

    </script>
</x-guest-layout>
