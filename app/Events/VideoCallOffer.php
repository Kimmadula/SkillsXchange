<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallOffer implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tradeId;
    public $fromUserId;
    public $fromUserName;
    public $toUserId;
    public $offer;
    public $callId;

    /**
     * Create a new event instance.
     */
    public function __construct($tradeId, $fromUserId, $fromUserName, $toUserId, $offer, $callId)
    {
        $this->tradeId = $tradeId;
        $this->fromUserId = $fromUserId;
        $this->fromUserName = $fromUserName;
        $this->toUserId = $toUserId;
        $this->offer = $offer;
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
        return 'video-call-offer';
    }
}