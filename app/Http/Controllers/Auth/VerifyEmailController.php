<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            // Check if user is admin and redirect accordingly
            if ($request->user()->role === 'admin') {
                return redirect()->route('admin.dashboard')->with('status', 'Email verified successfully!');
            }
            return redirect()->route('dashboard')->with('status', 'Email verified successfully!');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // After email verification, redirect based on user role
        if ($request->user()->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('status', 'Email verified successfully! Your account is now pending admin approval. You can browse the site but some features may be limited until approved.');
        }
        return redirect()->route('dashboard')->with('status', 'Email verified successfully! Your account is now pending admin approval. You can browse the site but some features may be limited until approved.');
    }
}
