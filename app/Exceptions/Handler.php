<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\View\ViewException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
        TokenMismatchException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'token',
        'api_token',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Log all exceptions with context
            $this->logException($e);
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Handle different types of exceptions
        if ($exception instanceof AuthenticationException) {
            return $this->handleAuthenticationException($request, $exception);
        }

        if ($exception instanceof TokenMismatchException) {
            return $this->handleTokenMismatchException($request, $exception);
        }

        if ($exception instanceof ViewException) {
            return $this->handleViewException($request, $exception);
        }

        if ($exception instanceof QueryException) {
            return $this->handleDatabaseException($request, $exception);
        }

        if ($exception instanceof ThrottleRequestsException) {
            return $this->handleThrottleException($request, $exception);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->handleNotFoundException($request, $exception);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->handleMethodNotAllowedException($request, $exception);
        }

        if ($exception instanceof AccessDeniedHttpException) {
            return $this->handleAccessDeniedException($request, $exception);
        }

        // Handle any other exceptions
        return $this->handleGenericException($request, $exception);
    }

    /**
     * Handle authentication exceptions
     */
    protected function handleAuthenticationException($request, AuthenticationException $exception)
    {
        Log::info('Authentication failed', [
            'url' => $request->url(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'exception' => $exception->getMessage()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Please log in to access this resource.'
            ], 401);
        }

        return redirect()->guest(route('login'))
            ->with('error', 'Please log in to access this page.');
    }

    /**
     * Handle CSRF token mismatch
     */
    protected function handleTokenMismatchException($request, TokenMismatchException $exception)
    {
        Log::warning('CSRF token mismatch', [
            'url' => $request->url(),
            'ip' => $request->ip(),
            'user_id' => Auth::id()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Token Mismatch',
                'message' => 'Your session has expired. Please refresh the page and try again.'
            ], 419);
        }

        return redirect()->back()
            ->with('error', 'Your session has expired. Please refresh the page and try again.')
            ->withInput($request->except($this->dontFlash));
    }

    /**
     * Handle view exceptions (undefined variables, etc.)
     */
    protected function handleViewException($request, ViewException $exception)
    {
        $message = $exception->getMessage();
        
        Log::warning('ViewException: ' . $message, [
            'url' => $request->url(),
            'user_id' => Auth::id(),
            'view' => $exception->getView() ?? 'unknown',
            'trace' => $exception->getTraceAsString()
        ]);

        // Check if it's a session-related error
        if (strpos($message, 'Undefined variable') !== false) {
            if (Auth::check()) {
                return redirect()->route('dashboard')
                    ->with('error', 'There was an issue loading your dashboard. Please try again.');
            } else {
                return redirect()->route('login')
                    ->with('error', 'Please log in to access your dashboard.');
            }
        }

        // For other view errors, show a generic error page
        return $this->handleGenericException($request, $exception);
    }

    /**
     * Handle database exceptions
     */
    protected function handleDatabaseException($request, QueryException $exception)
    {
        Log::error('Database error: ' . $exception->getMessage(), [
            'url' => $request->url(),
            'user_id' => Auth::id(),
            'sql' => $exception->getSql(),
            'bindings' => $exception->getBindings()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Database Error',
                'message' => 'A database error occurred. Please try again later.'
            ], 500);
        }

        return redirect()->back()
            ->with('error', 'A database error occurred. Please try again later.');
    }

    /**
     * Handle throttle exceptions
     */
    protected function handleThrottleException($request, ThrottleRequestsException $exception)
    {
        Log::warning('Rate limit exceeded', [
            'url' => $request->url(),
            'ip' => $request->ip(),
            'user_id' => Auth::id()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'You have made too many requests. Please wait before trying again.'
            ], 429);
        }

        return redirect()->back()
            ->with('error', 'You have made too many requests. Please wait before trying again.');
    }

    /**
     * Handle 404 not found exceptions
     */
    protected function handleNotFoundException($request, NotFoundHttpException $exception)
    {
        Log::info('Page not found', [
            'url' => $request->url(),
            'ip' => $request->ip(),
            'user_id' => Auth::id()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'The requested resource was not found.'
            ], 404);
        }

        return response()->view('errors.404', [], 404);
    }

    /**
     * Handle method not allowed exceptions
     */
    protected function handleMethodNotAllowedException($request, MethodNotAllowedHttpException $exception)
    {
        Log::warning('Method not allowed', [
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => Auth::id()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Method Not Allowed',
                'message' => 'The requested method is not allowed for this resource.'
            ], 405);
        }

        return response()->view('errors.405', [], 405);
    }

    /**
     * Handle access denied exceptions
     */
    protected function handleAccessDeniedException($request, AccessDeniedHttpException $exception)
    {
        Log::warning('Access denied', [
            'url' => $request->url(),
            'ip' => $request->ip(),
            'user_id' => Auth::id()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access Denied',
                'message' => 'You do not have permission to access this resource.'
            ], 403);
        }

        return response()->view('errors.403', [], 403);
    }

    /**
     * Handle generic exceptions
     */
    protected function handleGenericException($request, Throwable $exception)
    {
        $this->logException($exception);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }

        // Check if user is authenticated and session is valid
        if (Auth::check()) {
            return redirect()->route('dashboard')
                ->with('error', 'An unexpected error occurred. Please try again.');
        } else {
            return redirect()->route('login')
                ->with('error', 'An error occurred. Please log in again.');
        }
    }

    /**
     * Log exception with context
     */
    protected function logException(Throwable $exception)
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'user_id' => Auth::id(),
            'url' => request()->url(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => Session::getId()
        ];

        if ($exception instanceof \Error) {
            Log::error('PHP Error: ' . $exception->getMessage(), $context);
        } elseif ($exception instanceof \Exception) {
            Log::error('Exception: ' . $exception->getMessage(), $context);
        } else {
            Log::error('Throwable: ' . $exception->getMessage(), $context);
        }
    }
}
