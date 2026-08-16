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

class Chatupdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat , $user2Id;
    public function __construct($chat , $user2Id)
    {
        $this->chat = $chat;
        $this->user2Id = $user2Id;

    }


    public function broadcastOn()
    {
        return ['user-'.$this->user2Id];
    }

    public function broadcastAs()
    {
        return 'getChatUsersBloc';
    }

    public function broadcastWith() : array
    {
        return (array) $this->chat;
    }
}
