<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     * This works for both authenticated and unauthenticated users.
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
        // Find the user by ID
        $user = User::findOrFail($id);

        // Verify the hash matches the user's email
        // Use getEmailForVerification() if available (from MustVerifyEmail), otherwise use email
        $email = method_exists($user, 'getEmailForVerification')
            ? $user->getEmailForVerification()
            : $user->email;
        $emailHash = sha1($email);
        if (!hash_equals((string) $hash, $emailHash)) {
            abort(403, 'Invalid verification link.');
        }

        // Check if email is already verified
        if ($user->hasVerifiedEmail()) {
            // Auto-login the user if not already logged in
            if (!Auth::check()) {
                Auth::login($user);
            }

            // Check if user is admin and redirect accordingly
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard')->with('status', 'Email verified successfully!');
            }
            return redirect()->route('dashboard')->with('status', 'Email verified successfully!');
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Auto-login the user after verification (if not already logged in)
        if (!Auth::check()) {
            Auth::login($user);
        }

        // After email verification, redirect based on user role
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('status', 'Email verified successfully! Your account is now pending admin approval. You can browse the site but some features may be limited until approved.');
        }
        return redirect()->route('dashboard')->with('status', 'Email verified successfully! Your account is now pending admin approval. You can browse the site but some features may be limited until approved.');
    }
}
