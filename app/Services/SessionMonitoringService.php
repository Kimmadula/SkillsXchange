<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class SessionMonitoringService
{
    /**
     * Monitor user session activity
     */
    public function monitorSession($userId = null)
    {
        try {
            $userId = $userId ?? Auth::id();
            
            if (!$userId) {
                return false;
            }

            $sessionId = Session::getId();
            $currentTime = time();
            
            // Update session activity
            $sessionData = [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'last_activity' => $currentTime,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => Session::get('session_start', $currentTime)
            ];

            // Store in cache
            Cache::put('session_' . $sessionId, $sessionData, config('session.lifetime', 60) * 60);
            
            // Update user's active sessions
            $this->updateUserSessions($userId, $sessionId, $sessionData);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Session monitoring error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user's active sessions
     */
    private function updateUserSessions($userId, $sessionId, $sessionData)
    {
        $userSessionsKey = 'user_sessions_' . $userId;
        $userSessions = Cache::get($userSessionsKey, []);
        
        $userSessions[$sessionId] = $sessionData;
        
        // Remove expired sessions
        $userSessions = array_filter($userSessions, function($session) {
            $lifetime = config('session.lifetime', 60) * 60;
            return $session['last_activity'] > (time() - $lifetime);
        });
        
        Cache::put($userSessionsKey, $userSessions, config('session.lifetime', 60) * 60);
    }

    /**
     * Check if session is expired
     */
    public function isSessionExpired($sessionId = null)
    {
        try {
            $sessionId = $sessionId ?? Session::getId();
            $sessionData = Cache::get('session_' . $sessionId);
            
            if (!$sessionData) {
                return true;
            }
            
            // For persistent sessions, only check if session is marked as persistent
            if (Session::get('persistent_session', false)) {
                return false; // Persistent sessions don't expire automatically
            }
            
            $lifetime = config('session.lifetime', 525600) * 60; // 1 year default
            $lastActivity = $sessionData['last_activity'] ?? 0;
            
            return (time() - $lastActivity) > $lifetime;
            
        } catch (\Exception $e) {
            Log::error('Session expiration check error: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Get session information
     */
    public function getSessionInfo($sessionId = null)
    {
        try {
            $sessionId = $sessionId ?? Session::getId();
            return Cache::get('session_' . $sessionId);
        } catch (\Exception $e) {
            Log::error('Get session info error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user's active sessions
     */
    public function getUserActiveSessions($userId = null)
    {
        try {
            $userId = $userId ?? Auth::id();
            
            if (!$userId) {
                return [];
            }
            
            $userSessionsKey = 'user_sessions_' . $userId;
            $userSessions = Cache::get($userSessionsKey, []);
            
            // Filter out expired sessions
            $lifetime = config('session.lifetime', 60) * 60;
            $activeSessions = array_filter($userSessions, function($session) use ($lifetime) {
                return $session['last_activity'] > (time() - $lifetime);
            });
            
            return $activeSessions;
            
        } catch (\Exception $e) {
            Log::error('Get user active sessions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Invalidate user sessions
     */
    public function invalidateUserSessions($userId, $exceptSessionId = null)
    {
        try {
            $userSessions = $this->getUserActiveSessions($userId);
            
            foreach ($userSessions as $sessionId => $sessionData) {
                if ($exceptSessionId && $sessionId === $exceptSessionId) {
                    continue;
                }
                
                // Remove session from cache
                Cache::forget('session_' . $sessionId);
            }
            
            // Clear user sessions
            Cache::forget('user_sessions_' . $userId);
            
            Log::info('Invalidated sessions for user: ' . $userId);
            
        } catch (\Exception $e) {
            Log::error('Invalidate user sessions error: ' . $e->getMessage());
        }
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions()
    {
        try {
            $lifetime = config('session.lifetime', 60) * 60;
            $expiredTime = time() - $lifetime;
            
            // This would need to be implemented based on your cache driver
            // For now, we'll just log that cleanup was attempted
            Log::info('Session cleanup attempted');
            
        } catch (\Exception $e) {
            Log::error('Session cleanup error: ' . $e->getMessage());
        }
    }

    /**
     * Get session statistics
     */
    public function getSessionStats()
    {
        try {
            $stats = [
                'total_active_sessions' => 0,
                'unique_users' => 0,
                'expired_sessions' => 0
            ];
            
            // This would need to be implemented based on your cache driver
            // For now, return basic stats
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Get session stats error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Force logout user from all sessions
     */
    public function forceLogoutUser($userId)
    {
        try {
            $this->invalidateUserSessions($userId);
            
            // You might want to dispatch an event here to notify other sessions
            // event(new UserForceLoggedOut($userId));
            
            Log::info('Force logged out user: ' . $userId);
            
        } catch (\Exception $e) {
            Log::error('Force logout user error: ' . $e->getMessage());
        }
    }
}
