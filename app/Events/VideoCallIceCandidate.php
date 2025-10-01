<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallIceCandidate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tradeId;
    public $toUserId;
    public $candidate;
    public $callId;

    /**
     * Create a new event instance.
     */
    public function __construct($tradeId, $toUserId, $candidate, $callId)
    {
        $this->tradeId = $tradeId;
        $this->toUserId = $toUserId;
        $this->candidate = $candidate;
        $this->callId = $callId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('trade.' . $this->tradeId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'video-call-ice-candidate';
    }
}