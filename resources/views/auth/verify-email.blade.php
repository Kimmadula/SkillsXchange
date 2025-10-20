<x-guest-layout>
    <!-- Success Alert -->
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="text-green-400" viewBox="0 0 20 20" fill="currentColor" width="20" height="20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">
                    Registration Successful!
                </h3>
                <div class="mt-2 text-sm text-green-700">
                    <p>Your account has been created successfully. Please check your email and verify your account to continue.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Verification Instructions -->
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="text-blue-400" viewBox="0 0 20 20" fill="currentColor" width="20" height="20" aria-hidden="true">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                    Email Verification Required
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>We've sent a verification link to your email address. Please:</p>
                    <ol class="mt-2 list-decimal list-inside space-y-1">
                        <li>Check your email inbox (and spam folder)</li>
                        <li>Click the verification link in the email</li>
                        <li>Return here to continue</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-blue-600 bg-blue-50 p-3 rounded-md">
            {{ session('status') }}
        </div>
    @endif

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-md">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div>
                <x-primary-button class="bg-blue-600 hover:bg-blue-700">
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>

    <!-- Additional Help -->
    <div class="mt-6 text-center">
        <p class="text-sm text-gray-500">
            Didn't receive the email? Check your spam folder or 
            <button onclick="document.querySelector('form[action=\'{{ route('verification.send') }}\']').submit()" class="text-blue-600 hover:text-blue-500 underline">
                click here to resend
            </button>
        </p>
    </div>
</x-guest-layout>
