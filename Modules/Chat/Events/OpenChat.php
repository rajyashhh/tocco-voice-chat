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

class OpenChat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat, $user2, $check_room, $isConversation;

    /**
     * See Conversation::$broadcastNow. Flipped to true only by
     * BroadcastChatMessage so the async job publishes synchronously and the
     * second broadcast-queue hop is removed for 1:1 delivery; in-request
     * callers leave it false and keep broadcasting off-request.
     */
    public $broadcastNow = false;

    public function __construct($chat, $user2, $check_room, $isConversation = true)
    {
        $this->chat = $chat;
        $this->user2 = $user2;
        $this->check_room = $check_room ;
        $this->isConversation = $isConversation ;
    }

    public function shouldBroadcastNow(): bool
    {
        return $this->broadcastNow;
    }

    public function broadcastOn() :array
    {
        if ($this->isConversation) {
            return [
                'user-' . $this->user2->id,
                'conversation-' . $this->check_room->id,
            ];
        }

        return [
            'user-' . $this->user2->id,
        ];

//        return ['user-'.$this->user2->id ,'conversation-'.$this->check_room->id];
    }

    public function broadcastAs()
    {
        return 'open_chat';
    }

    public function broadcastWith() : array
    {
        return (array) $this->chat;
    }
}
