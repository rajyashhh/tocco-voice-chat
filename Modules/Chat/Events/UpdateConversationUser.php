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

class UpdateConversationUser implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message ;
    public $user2 ;
    public function __construct($message ,$user2 )
    {
        $this->message = $message;
        $this->user2 = $user2;
    }

    public function broadcastOn()
    {
        return ['conversation-user'.$this->user2->id];
    }

    public function broadcastAs()
    {
        return 'update-user-conversation';
    }

    public function broadcastWith() : array
    {
        return (array) $this->message;
    }
}
