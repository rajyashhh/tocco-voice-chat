<?php

namespace Modules\Chat\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Events\Chat;
use Modules\Chat\Events\Conversation;
use Modules\Chat\Events\OpenChat;

/**
 * Fans out a freshly persisted 1:1 chat message over the realtime transport.
 *
 * Broadcasting used to run synchronously inside the HTTP request that created the
 * message, so the sender's response was blocked on the broadcast driver (the
 * source of the Pusher quota incident's latency). The write path now persists the
 * message + server_seq, dispatches this job, and returns immediately; the actual
 * fan-out happens on the jo-job worker.
 *
 * It carries only the already-rendered payloads (built once in the request with
 * the correct request/locale context) plus the recipient and room ids, then
 * re-emits the same three events the controller used to fire inline. Channels and
 * payload shapes are unchanged — only the execution context moved off the request.
 */
class BroadcastChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $chatRoomId,
        private int $senderId,
        private ?int $user2Id,
        private array $messagePayload,
        private array $roomPayload,
    ) {
    }

    public function handle(): void
    {
        $room = ChatRoom::find($this->chatRoomId);

        if (!$room) {
            return;
        }

        $user2 = $this->user2Id ? User::withoutAppends()->find($this->user2Id) : null;

        // Mirror the controller's original fallback: when there is no second user
        // (single-user room) OpenChat targets the sender's own channel.
        $openChatTarget = $user2 ?? User::withoutAppends()->find($this->senderId);

        try {
            // This job already runs off-request on the jo-job worker, so the
            // broadcasts must publish to Centrifugo/Pusher synchronously HERE.
            // The events are ShouldBroadcast (queued by default) — without
            // forcing now(), event() would push a SECOND broadcast job onto the
            // default broadcast queue before the transport publish ever runs,
            // adding a serial queue hop that delayed 1:1 realtime delivery
            // (groups have no such hop; BroadcastGroupMessage publishes
            // directly). broadcastNow=true makes BroadcastManager::queue
            // dispatch the broadcast inline, collapsing the two hops into one.
            $conversation = new Conversation($this->messagePayload, $user2, $room);
            $conversation->broadcastNow = true;
            event($conversation);

            $chat = new Chat($this->roomPayload, $user2);
            $chat->broadcastNow = true;
            event($chat);

            // isConversation=true so OpenChat also publishes to conversation-{roomId}
            // (→ chat:dm.{min}_{max}). With false it only hit user-{id}, so the
            // peer viewing the open chat never got the "opened/seen" signal on the
            // shared DM channel.
            $openChat = new OpenChat($this->roomPayload, $openChatTarget, $room, true);
            $openChat->broadcastNow = true;
            event($openChat);
        } catch (\Throwable $e) {
            Log::warning('BroadcastChatMessage failed', [
                'chat_room_id' => $this->chatRoomId,
                'user2_id' => $this->user2Id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
