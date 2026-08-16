<?php

namespace Modules\TaskStream\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStreamRespondInvitation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $targetUserId;
    public $data;

    public function __construct($targetUserId, $data)
    {
        $this->targetUserId = $targetUserId;
        $this->data = $data;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user-' . $this->targetUserId);
    }

    public function broadcastAs(): string
    {
        return 'task.stream.respond.invitation';
    }


    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->data['user_id'],
            'user_name' => $this->data['user_name'],
            'room_id' => $this->data['room_id'],
            'task_stream_id' => $this->data['task_stream_id'],
        ];
    }
}
