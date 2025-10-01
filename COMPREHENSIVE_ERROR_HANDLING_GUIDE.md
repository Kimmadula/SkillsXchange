# Comprehensive Error Handling & Session Management Guide

## Overview

This guide documents the comprehensive error handling and session management system implemented in the SkillsXchange application. The system provides robust error trapping, session expiration handling, and user-friendly error recovery.

## Features Implemented

### 1. Session Management System

#### SessionExpirationMiddleware
- **Location**: `app/Http/Middleware/SessionExpirationMiddleware.php`
- **Purpose**: Monitors user sessions and handles expiration
- **Features**:
  - Automatic session expiration detection
  - Cache cleanup on session expiry
  - Concurrent session management
  - Comprehensive error logging
  - Security-focused session invalidation

#### SessionMonitoringService
- **Location**: `app/Services/SessionMonitoringService.php`
- **Purpose**: Centralized session monitoring and management
- **Features**:
  - Real-time session activity tracking
  - User session statistics
  - Session cleanup utilities
  - Force logout capabilities
  - Cache-based session storage

#### SessionController
- **Location**: `app/Http/Controllers/SessionController.php`
- **Purpose**: API endpoints for session management
- **Endpoints**:
  - `POST /user/keep-alive` - Extend session
  - `GET /user/session-status` - Check session status
  - `GET /user/active-sessions` - Get user's active sessions
  - `DELETE /user/sessions/{sessionId}` - Invalidate specific session
  - `POST /user/logout-all` - Force logout from all sessions
  - `GET /admin/session-stats` - Admin session statistics
  - `POST /admin/session-cleanup` - Cleanup expired sessions

### 2. Error Handling System

#### Enhanced Exception Handler
- **Location**: `app/Exceptions/Handler.php`
- **Purpose**: Comprehensive exception handling
- **Features**:
  - Specific handling for different exception types
  - Contextual error logging
  - User-friendly error messages
  - Automatic redirects for common errors
  - JSON API error responses

#### ErrorHandlingMiddleware
- **Location**: `app/Http/Middleware/ErrorHandlingMiddleware.php`
- **Purpose**: Pre and post-request error handling
- **Features**:
  - Suspicious activity detection
  - Request validation
  - Error recovery mechanisms
  - Comprehensive logging

### 3. Client-Side Session Monitoring

#### Enhanced Session Monitor
- **Location**: `public/js/session-monitor-enhanced.js`
- **Purpose**: Client-side session management
- **Features**:
  - Real-time session monitoring
  - Automatic session extension
  - User activity detection
  - Connection status monitoring
  - Interactive session warnings
  - Retry mechanisms for failed requests

## Configuration

### Session Configuration
```php
// config/session.php
'lifetime' => env('SESSION_LIFETIME', 60), // 60 minutes
'expire_on_close' => false,
'encrypt' => true,
'files' => storage_path('framework/sessions'),
'connection' => null,
'table' => 'sessions',
'store' => null,
'lottery' => [2, 100],
'cookie' => 'laravel_session',
'path' => '/',
'domain' => null,
'secure' => env('SESSION_SECURE_COOKIE', false),
'http_only' => true,
'same_site' => 'lax',
```

### Middleware Registration
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\SessionExpirationMiddleware::class,
        \App\Http\Middleware\ErrorHandlingMiddleware::class,
    ],
];
```

## Error Types Handled

### 1. Authentication Errors
- **Unauthenticated users**: Redirect to login
- **Session expired**: Clear session and redirect
- **Invalid tokens**: Regenerate and redirect

### 2. View Errors
- **Undefined variables**: Redirect to dashboard with error message
- **Template errors**: Show generic error page
- **Missing views**: Fallback to error pages

### 3. Database Errors
- **Connection failures**: Log and show user-friendly message
- **Query errors**: Log details and redirect
- **Transaction failures**: Rollback and notify user

### 4. Validation Errors
- **Form validation**: Show field-specific errors
- **CSRF token mismatch**: Refresh page and retry
- **Rate limiting**: Show appropriate message

### 5. HTTP Errors
- **404 Not Found**: Custom error page
- **403 Forbidden**: Access denied page
- **405 Method Not Allowed**: Method error page
- **500 Internal Server Error**: Generic error page

## Session Lifecycle

### 1. Session Creation
1. User logs in successfully
2. Session is created with unique ID
3. Last activity timestamp is set
4. Session data is stored in cache
5. Client-side monitoring begins

### 2. Session Monitoring
1. Every 30 seconds: Check session status
2. Every 5 minutes: Send keep-alive request
3. On user activity: Reset timers
4. On page visibility change: Pause/resume monitoring

### 3. Session Warning
1. 5 minutes before expiry: Show warning modal
2. User can extend session or logout
3. If no action: Automatic logout
4. If extended: Reset timers

### 4. Session Expiration
1. Session lifetime exceeded
2. All timers cleared
3. User data cleaned from cache
4. Redirect to login with expired flag
5. Show appropriate message

## Security Features

### 1. Session Security
- **Secure cookies**: HTTPS only in production
- **HttpOnly**: Prevents XSS attacks
- **SameSite**: CSRF protection
- **Encryption**: Session data encrypted
- **Regeneration**: Token regeneration on login

### 2. Error Security
- **Sensitive data**: Never exposed in error messages
- **Stack traces**: Only shown in debug mode
- **Logging**: Comprehensive but secure logging
- **Rate limiting**: Prevents abuse

### 3. Monitoring Security
- **Suspicious activity**: IP-based monitoring
- **Concurrent sessions**: Configurable limits
- **Force logout**: Admin capability
- **Audit trail**: Complete session logging

## Usage Examples

### 1. Extending Session (Client-side)
```javascript
// Extend session manually
SessionMonitor.extendSession();

// Check if session is expired
if (SessionMonitor.isExpired()) {
    // Handle expired session
}

// Get last activity time
const lastActivity = SessionMonitor.getLastActivity();
```

### 2. Session Management (Server-side)
```php
// Get session status
$sessionInfo = $sessionMonitoringService->getSessionInfo();

// Check if session is expired
$isExpired = $sessionMonitoringService->isSessionExpired();

// Get user's active sessions
$activeSessions = $sessionMonitoringService->getUserActiveSessions($userId);

// Force logout user
$sessionMonitoringService->forceLogoutUser($userId);
```

### 3. Error Handling (Server-side)
```php
// Log exception with context
Log::error('Custom error message', [
    'user_id' => Auth::id(),
    'url' => request()->url(),
    'additional_data' => $data
]);

// Handle specific exception
try {
    // Risky operation
} catch (SpecificException $e) {
    Log::error('Specific error: ' . $e->getMessage());
    return redirect()->back()->with('error', 'User-friendly message');
}
```

## Monitoring and Logging

### 1. Session Logs
- Session creation and destruction
- Activity timestamps
- Expiration events
- Concurrent session management
- Force logout events

### 2. Error Logs
- Exception details with context
- User information
- Request details
- Stack traces (debug mode)
- Performance metrics

### 3. Security Logs
- Suspicious activity
- Failed authentication attempts
- Rate limiting events
- CSRF token mismatches
- Session hijacking attempts

## Troubleshooting

### 1. Common Issues

#### Session Not Expiring
- Check session lifetime configuration
- Verify middleware registration
- Check cache configuration
- Review session storage

#### Errors Not Being Caught
- Verify exception handler registration
- Check middleware order
- Review error reporting configuration
- Test with different error types

#### Client-side Monitoring Not Working
- Check JavaScript console for errors
- Verify CSRF token availability
- Test API endpoints manually
- Check network connectivity

### 2. Debug Mode
```php
// Enable debug mode in .env
APP_DEBUG=true
LOG_LEVEL=debug

// Check logs
tail -f storage/logs/laravel.log
```

### 3. Testing
```bash
# Test session endpoints
curl -X POST http://localhost/user/keep-alive \
  -H "X-CSRF-TOKEN: your-token" \
  -H "Content-Type: application/json"

# Test error handling
curl -X GET http://localhost/nonexistent-page
```

## Best Practices

### 1. Session Management
- Set appropriate session lifetime
- Monitor session usage patterns
- Implement session cleanup
- Use secure session storage

### 2. Error Handling
- Log all errors with context
- Provide user-friendly messages
- Implement proper error recovery
- Monitor error rates

### 3. Security
- Regular security audits
- Monitor suspicious activity
- Implement rate limiting
- Keep dependencies updated

## Performance Considerations

### 1. Session Storage
- Use appropriate cache driver
- Implement session cleanup
- Monitor memory usage
- Consider session clustering

### 2. Error Handling
- Minimize error logging overhead
- Use appropriate log levels
- Implement log rotation
- Monitor performance impact

### 3. Client-side Monitoring
- Optimize polling intervals
- Implement efficient event handling
- Use request batching
- Monitor network usage

## Conclusion

This comprehensive error handling and session management system provides:

- **Robust session management** with automatic expiration and renewal
- **Comprehensive error handling** for all types of exceptions
- **Security-focused design** with monitoring and protection
- **User-friendly experience** with clear error messages and recovery
- **Administrative tools** for session and error management
- **Performance optimization** with efficient monitoring and logging

The system ensures that users are properly authenticated, sessions are managed securely, and errors are handled gracefully throughout the application.
