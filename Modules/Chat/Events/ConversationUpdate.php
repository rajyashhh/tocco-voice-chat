<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConversationUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message ;
    public $check_room ;
    public function __construct($message ,$check_room)
    {
        $this->message = $message;
        $this->check_room = $check_room;
    }

    public function broadcastOn()
    {
        return ['conversation-'.$this->check_room];
    }

    public function broadcastAs()
    {
        return 'update-conversation-list';
    }

    public function broadcastWith() : array
    {
        return (array) $this->message;
    }
}
