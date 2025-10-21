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

class TaskCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $tradeId;

    /**
     * Create a new event instance.
     */
    public function __construct(TradeTask $task, $tradeId)
    {
        $this->task = $task;
        $this->tradeId = $tradeId;
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
            'task' => $this->task,
            'creator_name' => $this->task->creator->firstname . ' ' . $this->task->creator->lastname,
            'assignee_name' => $this->task->assignee->firstname . ' ' . $this->task->assignee->lastname
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'task-created';
    }
}
