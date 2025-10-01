<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Trade channel authorization (for public channels with dash)
Broadcast::channel('trade-{tradeId}', function ($user, $tradeId) {
    $trade = \App\Models\Trade::find($tradeId);
    
    if (!$trade) {
        return false;
    }
    
    // User can listen if they own the trade or are an accepted participant
    return $trade->user_id === $user->id || 
           $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists();
});

// Trade private channel authorization (for private channels with dot)
Broadcast::channel('trade.{tradeId}', function ($user, $tradeId) {
    $trade = \App\Models\Trade::find($tradeId);
    
    if (!$trade) {
        return false;
    }
    
    // User can listen if they own the trade or are an accepted participant
    return $trade->user_id === $user->id || 
           $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists();
});

// Trade private channel authorization (for private channels with private- prefix)
Broadcast::channel('private-trade.{tradeId}', function ($user, $tradeId) {
    $trade = \App\Models\Trade::find($tradeId);
    
    if (!$trade) {
        return false;
    }
    
    // User can listen if they own the trade or are an accepted participant
    return $trade->user_id === $user->id || 
           $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists();
});