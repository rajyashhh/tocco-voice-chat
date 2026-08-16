<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ReactMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message ;
    public $user2 ;
    public $check_room ;
    public function __construct($message ,$user2 ,$check_room)
    {
        $this->message = $message;
        $this->user2 = $user2;
        $this->check_room = $check_room;
    }

    public function broadcastOn()
    {
        return ["user-$this->user2", 'conversation-'.$this->check_room->id];
    }

    public function broadcastAs()
    {
        return 'react-event';
    }

    public function broadcastWith() : array
    {
        return (array) $this->message;
    }
}
