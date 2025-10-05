<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\ViewException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorHandlingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check for common issues before processing the request
            $this->preRequestChecks($request);
            
            $response = $next($request);
            
            // Post-request checks
            $this->postRequestChecks($request, $response);
            
            return $response;
            
        } catch (ViewException $e) {
            return $this->handleViewException($request, $e);
        } catch (QueryException $e) {
            return $this->handleDatabaseException($request, $e);
        } catch (ValidationException $e) {
            $this->handleValidationException($request, $e);
            throw $e; // This line won't execute but satisfies the linter
        } catch (HttpException $e) {
            $this->handleHttpException($request, $e);
            throw $e; // This line won't execute but satisfies the linter
        } catch (\Exception $e) {
            return $this->handleGenericException($request, $e);
        } catch (\Error $e) {
            return $this->handleFatalError($request, $e);
        }
    }

    /**
     * Pre-request checks
     */
    private function preRequestChecks(Request $request)
    {
        // Check if user is authenticated but session is invalid
        if (Auth::check() && !Session::has('last_activity')) {
            Log::warning('Authenticated user without session activity', [
                'user_id' => Auth::id(),
                'url' => $request->url(),
                'ip' => $request->ip()
            ]);
            
            // Set initial session activity
            Session::put('last_activity', time());
        }
        
        // Check for suspicious activity
        $this->checkSuspiciousActivity($request);
    }

    /**
     * Post-request checks
     */
    private function postRequestChecks(Request $request, $response)
    {
        // Log successful requests for monitoring
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->logSuccessfulRequest($request);
        }
    }

    /**
     * Check for suspicious activity
     */
    private function checkSuspiciousActivity(Request $request)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        // Check for rapid requests from same IP
        $key = 'suspicious_activity_' . $ip;
        $requests = Session::get($key, []);
        $currentTime = time();
        
        // Remove requests older than 1 minute
        $requests = array_filter($requests, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 60;
        });
        
        $requests[] = $currentTime;
        Session::put($key, $requests);
        
        // Adjust threshold based on request type
        $threshold = 100; // Increased threshold to prevent false positives
        
        // Higher threshold for chat message polling
        if (str_contains($request->path(), 'chat') && str_contains($request->path(), 'messages')) {
            $threshold = 100; // Allow more requests for chat polling
        }
        
        // If more than threshold requests in 1 minute, log as suspicious
        if (count($requests) > $threshold) {
            Log::warning('Suspicious activity detected', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'request_count' => count($requests),
                'url' => $request->url(),
                'threshold' => $threshold
            ]);
        }
    }

    /**
     * Log successful request
     */
    private function logSuccessfulRequest(Request $request)
    {
        // Only log for authenticated users and important pages
        if (Auth::check() && $this->isImportantPage($request)) {
            Log::info('Successful request', [
                'user_id' => Auth::id(),
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip()
            ]);
        }
    }

    /**
     * Check if page is important for logging
     */
    private function isImportantPage(Request $request)
    {
        $importantRoutes = [
            'dashboard',
            'trades.create',
            'trades.matches',
            'trades.requests',
            'trades.ongoing',
            'tasks.index',
            'trades.notifications'
        ];
        
        $routeName = $request->route() ? $request->route()->getName() : null;
        
        return $routeName && in_array($routeName, $importantRoutes);
    }

    /**
     * Handle view exceptions
     */
    private function handleViewException(Request $request, ViewException $e)
    {
        Log::error('View Exception: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'url' => $request->url(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if (Auth::check()) {
            return redirect()->route('dashboard')
                ->with('error', 'There was an issue loading the page. Please try again.');
        } else {
            return redirect()->route('login')
                ->with('error', 'Please log in to access this page.');
        }
    }

    /**
     * Handle database exceptions
     */
    private function handleDatabaseException(Request $request, QueryException $e)
    {
        Log::error('Database Exception: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'url' => $request->url(),
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings()
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
     * Handle validation exceptions
     */
    private function handleValidationException(Request $request, ValidationException $e): void
    {
        Log::info('Validation Exception: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'url' => $request->url(),
            'errors' => $e->errors()
        ]);

        // Let Laravel handle validation exceptions normally
        throw $e;
    }

    /**
     * Handle HTTP exceptions
     */
    private function handleHttpException(Request $request, HttpException $e): void
    {
        Log::warning('HTTP Exception: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'url' => $request->url(),
            'status_code' => $e->getStatusCode()
        ]);

        // Let Laravel handle HTTP exceptions normally
        throw $e;
    }

    /**
     * Handle generic exceptions
     */
    private function handleGenericException(Request $request, \Exception $e)
    {
        Log::error('Generic Exception: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'url' => $request->url(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }

        if (Auth::check()) {
            return redirect()->route('dashboard')
                ->with('error', 'An unexpected error occurred. Please try again.');
        } else {
            return redirect()->route('login')
                ->with('error', 'An error occurred. Please log in again.');
        }
    }

    /**
     * Handle fatal errors
     */
    private function handleFatalError(Request $request, \Error $e)
    {
        Log::critical('Fatal Error: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'url' => $request->url(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Fatal Error',
                'message' => 'A critical error occurred. Please contact support.'
            ], 500);
        }

        return redirect()->route('login')
            ->with('error', 'A critical error occurred. Please log in again.');
    }
}
