<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\VideoCallOffer;
use App\Events\VideoCallAnswer;
use App\Events\VideoCallIceCandidate;
use App\Events\VideoCallEnd;
use App\Models\Trade;

class VideoCallController extends Controller
{
    /**
     * Send a video call offer
     */
    public function sendOffer(Request $request, Trade $trade)
    {
        $user = Auth::user();
        
        // Verify user is part of this trade
        if (!$this->isUserInTrade($user, $trade)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'offer' => 'required|array',
            'callId' => 'required|string'
        ]);
        
        try {
            // Get the other user in the trade
            $otherUser = $this->getOtherUserInTrade($user, $trade);
            
            // Create the event
            $event = new VideoCallOffer(
                $trade->id,
                $user->id,
                $user->firstname . ' ' . $user->lastname,
                $otherUser->id,
                $request->offer,
                $request->callId
            );
            
            // Broadcast the offer
            event($event);
            
            // Also try broadcasting directly
            broadcast($event);
            
            Log::info('Video call offer sent', [
                'trade_id' => $trade->id,
                'from_user_id' => $user->id,
                'to_user_id' => $otherUser->id,
                'call_id' => $request->callId,
                'channel' => 'trade.' . $trade->id,
                'event_name' => 'video-call-offer'
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error sending video call offer: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send offer'], 500);
        }
    }
    
    /**
     * Send a video call answer
     */
    public function sendAnswer(Request $request, Trade $trade)
    {
        $user = Auth::user();
        
        // Verify user is part of this trade
        if (!$this->isUserInTrade($user, $trade)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'answer' => 'required|array',
            'callId' => 'required|string',
            'toUserId' => 'required|integer'
        ]);
        
        try {
            // Broadcast the answer
            event(new VideoCallAnswer(
                $trade->id,
                $request->toUserId,
                $request->answer,
                $request->callId
            ));
            
            Log::info('Video call answer sent', [
                'trade_id' => $trade->id,
                'from_user_id' => $user->id,
                'to_user_id' => $request->toUserId,
                'call_id' => $request->callId
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error sending video call answer: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send answer'], 500);
        }
    }
    
    /**
     * Send ICE candidate
     */
    public function sendIceCandidate(Request $request, Trade $trade)
    {
        $user = Auth::user();
        
        // Verify user is part of this trade
        if (!$this->isUserInTrade($user, $trade)) {
            Log::warning('Unauthorized ICE candidate attempt', [
                'user_id' => $user->id,
                'trade_id' => $trade->id
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'candidate' => 'required|array',
            'callId' => 'required|string',
            'toUserId' => 'required|integer'
        ]);
        
        // Additional validation for ICE candidate structure
        if (!isset($request->candidate['candidate']) || !isset($request->candidate['sdpMid']) || !isset($request->candidate['sdpMLineIndex'])) {
            Log::warning('Invalid ICE candidate structure', [
                'candidate' => $request->candidate,
                'user_id' => $user->id,
                'trade_id' => $trade->id
            ]);
            return response()->json(['error' => 'Invalid ICE candidate format'], 400);
        }
        
        try {
            // Verify the target user exists and is part of the trade
            $targetUser = \App\Models\User::find($request->toUserId);
            if (!$targetUser) {
                Log::warning('ICE candidate target user not found', [
                    'target_user_id' => $request->toUserId,
                    'trade_id' => $trade->id
                ]);
                return response()->json(['error' => 'Target user not found'], 404);
            }
            
            // Broadcast the ICE candidate
            event(new VideoCallIceCandidate(
                $trade->id,
                $request->toUserId,
                $request->candidate,
                $request->callId
            ));
            
            Log::info('ICE candidate sent successfully', [
                'trade_id' => $trade->id,
                'from_user_id' => $user->id,
                'to_user_id' => $request->toUserId,
                'call_id' => $request->callId
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error sending ICE candidate: ' . $e->getMessage(), [
                'trade_id' => $trade->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to send ICE candidate'], 500);
        }
    }
    
    /**
     * End video call
     */
    public function endCall(Request $request, Trade $trade)
    {
        $user = Auth::user();
        
        // Verify user is part of this trade
        if (!$this->isUserInTrade($user, $trade)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'callId' => 'required|string'
        ]);
        
        try {
            // Broadcast call end
            event(new VideoCallEnd(
                $trade->id,
                $user->id,
                $request->callId
            ));
            
            Log::info('Video call ended', [
                'trade_id' => $trade->id,
                'user_id' => $user->id,
                'call_id' => $request->callId
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error ending video call: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to end call'], 500);
        }
    }
    
    /**
     * Check if user is part of the trade
     */
    private function isUserInTrade($user, $trade)
    {
        // User is either the trade owner or has an accepted request for this trade
        return $user->id === $trade->user_id || 
               $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists();
    }
    
    /**
     * Poll for video call messages (HTTP fallback)
     */
    public function pollMessages(Request $request, Trade $trade)
    {
        $user = Auth::user();
        
        // Verify user is part of this trade
        if (!$this->isUserInTrade($user, $trade)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        try {
            // For now, return empty messages
            // In a real implementation, you would store messages in database/cache
            // and retrieve them here
            return response()->json([
                'success' => true,
                'messages' => []
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error polling video call messages: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to poll messages'], 500);
        }
    }
    
    /**
     * Get the other user in the trade
     */
    private function getOtherUserInTrade($user, $trade)
    {
        if ($user->id === $trade->user_id) {
            // Current user is the trade owner, get the requester
            $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
            return $acceptedRequest ? $acceptedRequest->requester : null;
        } else {
            // Current user is a requester, get the trade owner
            return $trade->user;
        }
    }
}