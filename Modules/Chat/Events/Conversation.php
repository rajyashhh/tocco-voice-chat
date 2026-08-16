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

class Conversation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message ;
    public $user2 ;
    public $check_room ;

    /**
     * When true, the event publishes synchronously (Laravel's
     * BroadcastManager::queue dispatches BroadcastEvent now instead of pushing
     * it onto the broadcast queue). The class stays ShouldBroadcast so the
     * in-request callers (entrance room / CP / chat-open) keep broadcasting
     * off-request as before; only BroadcastChatMessage — already running async
     * on the jo-job worker — flips this to remove the second queue hop that was
     * delaying 1:1 realtime delivery.
     */
    public $broadcastNow = false;

    public function __construct($message ,$user2 ,$check_room)
    {
        $this->message = $message;
        $this->user2 = $user2;
        $this->check_room = $check_room;
    }

    public function shouldBroadcastNow(): bool
    {
        return $this->broadcastNow;
    }

    public function broadcastOn()
    {
        // Shared 1:1 DM channel. Emit the PAIR form (conversation-pair-{a}_{b})
        // built from the room's two participant ids when both are known, so the
        // ChannelMapper resolves it to chat:dm.{min}_{max} deterministically with
        // no DB lookup — byte-for-byte the channel the client subscribes to and the
        // auth surface signs. The legacy conversation-{roomId} form is kept only as
        // a fallback (single-user/corrupt rooms): the mapper there re-derives the
        // pair from the DB and, if that fails, logs + lands on a dead channel.
        $a = (int) ($this->check_room->user_id ?? 0);
        $b = (int) ($this->check_room->user_id2 ?? 0);

        if ($a > 0 && $b > 0) {
            $channels = ['conversation-pair-'.min($a, $b).'_'.max($a, $b)];
        } else {
            $channels = ['conversation-'.$this->check_room->id];
        }

        // Also fan the MESSAGE to the recipient's personal channel (user-{id} ->
        // Centrifugo user:#{id}). The realtime client is always subscribed to its
        // own user channel but only subscribes to the shared chat:dm channel while
        // the conversation is open — so without this, a 1:1 message never reached
        // the recipient over Centrifugo unless they had the chat open. The client
        // applies it to drift, which updates BOTH the chats list (order/preview/
        // unread) and the open conversation (it also renders from drift).
        if ($this->user2 && isset($this->user2->id) && $this->user2->id) {
            $channels[] = 'user-'.$this->user2->id;
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'update-conversation-list';
    }

    public function broadcastWith() : array
    {
        $data = (array) $this->message;

    
        return $data;
    
    }
}
