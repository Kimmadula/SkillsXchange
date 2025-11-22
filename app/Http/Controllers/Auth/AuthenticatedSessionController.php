<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\SessionReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Block banned or suspended users at login
        if (method_exists($user, 'isAccountRestricted') && $user->isAccountRestricted()) {
            $ban = method_exists($user, 'getCurrentBan') ? $user->getCurrentBan() : null;
            $suspension = method_exists($user, 'getCurrentSuspension') ? $user->getCurrentSuspension() : null;

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $ban
                ? ('Your account has been permanently banned. Reason: ' . ($ban->reason ?? ''))
                : 'Your account is currently suspended.';
            if ($suspension && $suspension->suspension_end) {
                $message .= ' Suspension ends on: ' . $suspension->suspension_end->format('M d, Y \a\t g:i A');
            } elseif ($suspension) {
                $message .= ' Suspension is indefinite.';
            }

            throw ValidationException::withMessages([
                'login' => [$message],
            ]);
        }

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Require verified email before accessing the app
        if ($user->email_verified_at === null) {
            // Keep the session authenticated so the user can access the
            // verification notice and resend link, but do not send them
            // to the dashboard until email is verified.
            return redirect()->route('verification.notice')
                ->with('status', 'Please verify your email address to continue.');
        }

        $request->session()->regenerate();

        // Check for session reminders on login
        try {
            $reminderService = new SessionReminderService();
            $reminderService->checkAndCreateReminders();
        } catch (\Exception $e) {
            // Log error but don't block login
            \Log::error('Session reminder check failed on login: ' . $e->getMessage());
        }

        // For regular users, redirect to dashboard
        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
