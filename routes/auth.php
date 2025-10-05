<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\GoogleEmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');
                
    // Email verification routes
    Route::get('email/verify', [EmailVerificationController::class, 'verify'])
                ->name('email.verify');
    Route::post('email/resend', [EmailVerificationController::class, 'resend'])
                ->name('email.resend');
                
    // Firebase Authentication Routes (replacing Laravel Socialite)

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Firebase Authentication Routes
    Route::get('firebase-login', function () {
        return view('auth.firebase-login');
    })->name('firebase.login');

    Route::get('firebase-register', function () {
        return view('auth.firebase-register');
    })->name('firebase.register');

    Route::get('firebase/verify-email', function () {
        return view('auth.firebase-verify-email');
    })->name('firebase.verify-email');

    Route::get('firebase/google-username', function () {
        return view('auth.google-username');
    })->name('firebase.google-username');

    Route::post('auth/firebase/callback', [App\Http\Controllers\Auth\FirebaseAuthController::class, 'callback'])
                ->name('firebase.callback');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');

    // Firebase Authentication Routes for authenticated users
    Route::post('auth/firebase/logout', [App\Http\Controllers\Auth\FirebaseAuthController::class, 'logout'])
                ->name('firebase.logout');

    Route::get('auth/firebase/user', [App\Http\Controllers\Auth\FirebaseAuthController::class, 'user'])
                ->name('firebase.user');

    Route::post('auth/firebase/verify-status', [App\Http\Controllers\Auth\FirebaseAuthController::class, 'updateVerificationStatus'])
                ->name('firebase.verify-status');

    Route::post('auth/firebase/google-callback', [App\Http\Controllers\Auth\FirebaseAuthController::class, 'googleCallback'])
                ->name('firebase.google-callback');

    // Firebase Profile Completion Routes (requires email verification)
    Route::get('profile/complete', [App\Http\Controllers\Auth\FirebaseAuthController::class, 'showProfileComplete'])
                ->middleware('firebase.email.verified')
                ->name('firebase.profile.complete');

    Route::post('profile/complete', [App\Http\Controllers\Auth\FirebaseAuthController::class, 'completeProfile'])
                ->middleware('firebase.email.verified')
                ->name('firebase.profile.complete.post');
});
