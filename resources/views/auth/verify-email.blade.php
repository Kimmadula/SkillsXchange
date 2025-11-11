<x-guest-layout>
    <!-- Success Messages -->
    @if(session('status') && !session('error'))
    <p style="color: #28a745; margin-bottom: 16px;">
        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
        {{ session('status') }}
    </p>
    @endif

    <!-- Error Messages -->
    @if(session('error'))
    <div style="background-color: #f8d7da; color: #842029; padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; border: 1px solid #f5c2c7; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: #842029; font-size: 1.2rem; cursor: pointer; padding: 0; margin-left: 12px;" aria-label="Close">&times;</button>
    </div>
    @endif

    <!-- Email Verification Instructions -->
    <div style="margin-bottom: 16px;">
        <h5 style="font-weight: 600; margin: 0 0 8px 0; font-size: 1rem; color: #333;">Email Verification Required</h5>
        <p style="margin: 0 0 8px 0; font-size: 0.9rem; color: #333;">Before proceeding, please verify your email address by clicking the link we sent to your email.</p>
        <p style="margin: 0; font-size: 0.9rem; color: #333;">If you didn't receive the email, click the button below to resend it.</p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" id="resendVerificationForm">
        @csrf

        <div class="form-footer">
            <div class="form-footer-right" style="width: 100%; justify-content: center;">
                <button type="submit" class="btn-primary">
                    {{ __('RESEND VERIFICATION EMAIL') }}
                </button>
            </div>
        </div>
    </form>

    <script>
        // Prevent double form submission
        document.getElementById('resendVerificationForm').addEventListener('submit', function(e) {
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
                submitBtn.textContent = 'RESEND VERIFICATION EMAIL';
            }, 5000);
        });
    </script>
</x-guest-layout>
