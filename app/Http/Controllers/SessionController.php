<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Services\SessionMonitoringService;

class SessionController extends Controller
{
    protected $sessionMonitoringService;

    public function __construct(SessionMonitoringService $sessionMonitoringService)
    {
        $this->sessionMonitoringService = $sessionMonitoringService;
    }

    /**
     * Keep session alive
     */
    public function keepAlive(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Update session activity
            $this->sessionMonitoringService->monitorSession();
            
            // Update last activity in session
            Session::put('last_activity', time());

            return response()->json([
                'status' => 'success',
                'message' => 'Session extended',
                'timestamp' => time()
            ]);

        } catch (\Exception $e) {
            Log::error('Keep alive error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to extend session'
            ], 500);
        }
    }

    /**
     * Get session status
     */
    public function getStatus(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'unauthenticated',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $sessionInfo = $this->sessionMonitoringService->getSessionInfo();
            $isExpired = $this->sessionMonitoringService->isSessionExpired();

            return response()->json([
                'status' => 'authenticated',
                'expired' => $isExpired,
                'session_info' => $sessionInfo,
                'user_id' => Auth::id(),
                'timestamp' => time()
            ]);

        } catch (\Exception $e) {
            Log::error('Get session status error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get session status'
            ], 500);
        }
    }

    /**
     * Get user's active sessions
     */
    public function getActiveSessions(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $activeSessions = $this->sessionMonitoringService->getUserActiveSessions();

            return response()->json([
                'status' => 'success',
                'sessions' => $activeSessions,
                'count' => count($activeSessions)
            ]);

        } catch (\Exception $e) {
            Log::error('Get active sessions error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get active sessions'
            ], 500);
        }
    }

    /**
     * Invalidate specific session
     */
    public function invalidateSession(Request $request, $sessionId)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $userId = Auth::id();
            $this->sessionMonitoringService->invalidateUserSessions($userId, $sessionId);

            return response()->json([
                'status' => 'success',
                'message' => 'Session invalidated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Invalidate session error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to invalidate session'
            ], 500);
        }
    }

    /**
     * Force logout from all sessions
     */
    public function forceLogoutAll(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $userId = Auth::id();
            $this->sessionMonitoringService->forceLogoutUser($userId);

            // Logout current session
            Auth::logout();
            Session::flush();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out from all sessions'
            ]);

        } catch (\Exception $e) {
            Log::error('Force logout all error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout from all sessions'
            ], 500);
        }
    }

    /**
     * Get session statistics (admin only)
     */
    public function getStats(Request $request)
    {
        try {
            if (!Auth::check() || Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied'
                ], 403);
            }

            $stats = $this->sessionMonitoringService->getSessionStats();

            return response()->json([
                'status' => 'success',
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Get session stats error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get session statistics'
            ], 500);
        }
    }

    /**
     * Cleanup expired sessions (admin only)
     */
    public function cleanupExpired(Request $request)
    {
        try {
            if (!Auth::check() || Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied'
                ], 403);
            }

            $this->sessionMonitoringService->cleanupExpiredSessions();

            return response()->json([
                'status' => 'success',
                'message' => 'Expired sessions cleaned up'
            ]);

        } catch (\Exception $e) {
            Log::error('Cleanup expired sessions error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cleanup expired sessions'
            ], 500);
        }
    }
}
