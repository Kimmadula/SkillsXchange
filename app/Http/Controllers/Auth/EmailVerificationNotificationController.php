<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }

    /**
     * Resend email verification for unauthenticated users (from login page).
     */
    public function resend(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided email address does not exist in our records.'],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')
                ->with('status', 'Your email is already verified. You can log in now.')
                ->withInput(['login' => $request->email]);
        }

        try {
            $user->sendEmailVerificationNotification();

            Log::info('Verification email resent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return redirect()->route('login')
                ->with('status', 'A new verification link has been sent to your email address.')
                ->withInput(['login' => $request->email]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('login')
                ->with('error', 'Failed to send verification email. Please try again later.')
                ->withInput(['login' => $request->email]);
        }
    }
}
