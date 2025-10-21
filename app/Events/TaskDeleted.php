<?php

namespace App\Events;

use App\Models\TradeTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $taskId;
    public $tradeId;
    public $creatorName;
    public $assigneeName;

    /**
     * Create a new event instance.
     */
    public function __construct($taskId, $tradeId, $creatorName, $assigneeName)
    {
        $this->taskId = $taskId;
        $this->tradeId = $tradeId;
        $this->creatorName = $creatorName;
        $this->assigneeName = $assigneeName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("trade-{$this->tradeId}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->taskId,
            'creator_name' => $this->creatorName,
            'assignee_name' => $this->assigneeName
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'task-deleted';
    }
}
