<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| These routes are used for health checks and monitoring in production.
| They help ensure the application is running correctly.
|
*/

Route::get('/health', function () {
    $status = [
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => app()->environment(),
    ];

    // Check database connection
    try {
        DB::connection()->getPdo();
        $status['database'] = 'connected';
    } catch (\Exception $e) {
        $status['database'] = 'disconnected';
        $status['status'] = 'unhealthy';
    }

    // Check cache
    try {
        Cache::put('health_check', 'ok', 60);
        $status['cache'] = 'working';
    } catch (\Exception $e) {
        $status['cache'] = 'failed';
        $status['status'] = 'unhealthy';
    }

    // Check Firebase configuration
    $firebaseConfig = config('firebase');
    if ($firebaseConfig['project_id'] && $firebaseConfig['api_key']) {
        $status['firebase'] = 'configured';
    } else {
        $status['firebase'] = 'not_configured';
        $status['status'] = 'unhealthy';
    }

    $httpStatus = $status['status'] === 'healthy' ? 200 : 503;
    
    return response()->json($status, $httpStatus);
});

Route::get('/health/detailed', function () {
    $detailed = [
        'application' => [
            'name' => config('app.name'),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'version' => '1.0.0',
        ],
        'database' => [
            'connection' => config('database.default'),
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => config('database.connections.mysql.database'),
        ],
        'firebase' => [
            'project_id' => config('firebase.project_id'),
            'auth_domain' => config('firebase.auth_domain'),
            'auth_enabled' => config('firebase.auth.enabled'),
            'email_verification' => config('firebase.auth.email_verification_required'),
        ],
        'cache' => [
            'driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ],
        'pusher' => [
            'app_id' => config('broadcasting.connections.pusher.app_id'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
        ],
    ];

    return response()->json($detailed);
});
