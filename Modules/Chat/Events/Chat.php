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

class Chat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat , $user2;

    /**
     * See Conversation::$broadcastNow. Flipped to true only by
     * BroadcastChatMessage so the async job publishes synchronously and the
     * second broadcast-queue hop is removed for 1:1 delivery; in-request
     * callers leave it false and keep broadcasting off-request.
     */
    public $broadcastNow = false;

    public function __construct($chat , $user2)
    {
        $this->chat = $chat;
        $this->user2 = $user2;

    }

    public function shouldBroadcastNow(): bool
    {
        return $this->broadcastNow;
    }


    public function broadcastOn()
    {
        // user2 can be null in single-user chat rooms — broadcast nowhere instead of crashing
        if (!$this->user2) {
            return [];
        }
        return ['user-'.$this->user2->id];
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
