<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EndRoomBoomEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $endData;
    public function __construct($endData)
    {
        $this->endData = $endData;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('end.room.boom');
    }

    public function broadcastAs(): string
    {
        return 'end_room_boom';
    }
}
