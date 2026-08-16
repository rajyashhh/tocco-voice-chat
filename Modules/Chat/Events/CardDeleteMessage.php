<?php

namespace Modules\Chat\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class CardDeleteMessage implements ShouldBroadcastNow
{
    use SerializesModels;

    public $chat , $user2;
    public function __construct($chat , $user2)
    {
        $this->chat = $chat;
        $this->user2 = $user2;

    }

    public function broadcastOn()
    {
        return ['user-'.$this->user2];
    }

    public function broadcastAs()
    {
        return 'card-delete-message';
    }

    public function broadcastWith() : array
    {
        return (array) $this->chat;
    }
}
