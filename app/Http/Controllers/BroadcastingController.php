<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BroadcastingController extends Controller
{
    /**
     * Handle broadcasting authentication
     */
    public function auth(Request $request)
    {
        try {
            $user = Auth::user();
            $channelName = $request->input('channel_name');
            $socketId = $request->input('socket_id');
            
            Log::info('Broadcasting auth request', [
                'user_id' => $user ? $user->id : null,
                'channel_name' => $channelName,
                'socket_id' => $socketId,
                'user_authenticated' => Auth::check()
            ]);
            
            if (!$user) {
                Log::warning('Broadcasting auth failed: User not authenticated');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // Handle private channels
            if (str_starts_with($channelName, 'private-')) {
                $originalChannelName = $channelName;
                $channelName = str_replace('private-', '', $channelName);
                
                // Check if user can access this channel
                if (str_starts_with($channelName, 'trade.')) {
                    $tradeId = str_replace('trade.', '', $channelName);
                    $trade = \App\Models\Trade::find($tradeId);
                    
                    if (!$trade) {
                        Log::warning('Broadcasting auth failed: Trade not found', ['trade_id' => $tradeId]);
                        return response()->json(['error' => 'Trade not found'], 403);
                    }
                    
                    // Check if user is authorized for this trade
                    $isAuthorized = $trade->user_id === $user->id || 
                                   $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists();
                    
                    if (!$isAuthorized) {
                        Log::warning('Broadcasting auth failed: User not authorized for trade', [
                            'user_id' => $user->id,
                            'trade_id' => $tradeId
                        ]);
                        return response()->json(['error' => 'Unauthorized for this trade'], 403);
                    }
                }
                
                // Use the original channel name for Pusher auth
                $channelName = $originalChannelName;
            }
            
            // Generate Pusher auth signature
            $pusher = app('pusher');
            
            Log::info('Pusher instance created', [
                'pusher_class' => get_class($pusher),
                'pusher_config' => [
                    'key' => config('broadcasting.connections.pusher.key'),
                    'secret' => config('broadcasting.connections.pusher.secret'),
                    'app_id' => config('broadcasting.connections.pusher.app_id'),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                ]
            ]);
            
            $auth = $pusher->socket_auth($channelName, $socketId);
            
            Log::info('Pusher auth generated', [
                'auth_result' => $auth,
                'auth_type' => gettype($auth),
                'auth_json' => json_encode($auth)
            ]);
            
            // Handle the case where Pusher returns a JSON string
            if (is_string($auth)) {
                $authData = json_decode($auth, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $auth = $authData;
                }
            }
            
            Log::info('Broadcasting auth successful', [
                'user_id' => $user->id,
                'channel_name' => $channelName,
                'socket_id' => $socketId,
                'final_auth' => $auth
            ]);
            
            return response()->json($auth);
            
        } catch (\Exception $e) {
            Log::error('Broadcasting auth error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'channel_name' => $request->input('channel_name'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
