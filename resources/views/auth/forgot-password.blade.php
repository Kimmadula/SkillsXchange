<x-guest-layout>
    <!-- Header -->
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-900">Reset Your Password</h2>
        <p class="mt-2 text-sm text-gray-600">
            Enter your email address and we'll send you a password reset link.
        </p>
    </div>

    <!-- Instructions -->
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="text-blue-400" viewBox="0 0 20 20" fill="currentColor" width="20" height="20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                    How it works
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Enter your email address below</li>
                        <li>Check your email for a reset link</li>
                        <li>Click the link to create a new password</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="Enter your email address" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                Back to Login
            </a>
            <x-primary-button class="bg-blue-600 hover:bg-blue-700">
                {{ __('Send Reset Link') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Additional Help -->
    <div class="mt-6 text-center">
        <p class="text-sm text-gray-500">
            Don't have an account? 
            <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-500 underline">
                Create one here
            </a>
        </p>
    </div>
</x-guest-layout>
