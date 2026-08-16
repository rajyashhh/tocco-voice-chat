<?php

namespace Modules\Chat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/**
 * Fans out a `group_deleted` teardown signal to every member of a just-deleted
 * group so connected clients drop the group's drift room from their offline-first
 * chats list immediately (REALTIME_CHAT_REBUILD_PLAN section 5.4 fan-out rules).
 *
 * Why a dedicated job (vs. reusing BroadcastGroupMessage):
 *  - On delete, GroupService marks every membership status='left' in the same
 *    transaction, so by the time this job runs there are NO active members left
 *    to resolve from chat_room_members. The recipient list is therefore CAPTURED
 *    before the teardown and passed in explicitly, rather than queried at run time.
 *  - The payload is a signal, not a chat_messages row: it carries no server_seq
 *    and creates no message. The client routes it to drift room deletion, never to
 *    the message pipeline (only 'update-conversation-list'/'getGroupMessageBloc'
 *    create rows).
 *
 * Delivery mirrors BroadcastGroupMessage: recipients are chunked and each chunk is
 * handed to the active broadcaster as an array of legacy `user-{id}` channels, so a
 * whole chunk is a single Centrifugo /broadcast call (Redis-native fan-out, no
 * per-message quota). The default connection is selected by the realtime_transport
 * flag, so under 'pusher' it publishes to the legacy `user-{id}` channels and under
 * 'centrifugo'/'dual' the same names flow through ChannelMapper -> `user:#{id}`.
 * Offline members never receive the signal but pick the deletion up via the REST
 * /sync/rooms safety net (the deleted group is excluded from that response).
 */
class BroadcastGroupDeleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Recipients published per broadcast call — bounds the Centrifugo /broadcast
     * request body for large groups while keeping the number of HTTP calls minimal.
     */
    private const RECIPIENT_CHUNK = 500;

    /**
     * @param  int        $groupRoomId  chat_rooms.id of the deleted group's room.
     * @param  int[]      $userIds      Member user ids captured BEFORE the teardown.
     * @param  string     $event        broadcastAs-style event name the client routes on.
     */
    public function __construct(
        private int $groupRoomId,
        private array $userIds,
        private string $event = 'group_deleted',
    ) {
    }

    public function handle(): void
    {
        $userIds = array_values(array_unique(array_filter(
            $this->userIds,
            fn ($id) => (int) $id > 0
        )));

        if (empty($userIds)) {
            return;
        }

        $broadcaster = Broadcast::connection();
        $payload = [
            'chat_room_id' => $this->groupRoomId,
            'room_id'      => $this->groupRoomId,
            'type'         => 'group_deleted',
        ];

        foreach (array_chunk($userIds, self::RECIPIENT_CHUNK) as $chunk) {
            $channels = array_map(fn ($id) => 'user-' . $id, $chunk);

            try {
                $broadcaster->broadcast($channels, $this->event, $payload);
            } catch (\Throwable $e) {
                // Never abort the remaining chunks; the REST /sync/rooms layer
                // (which excludes the deleted group) is the safety net for a
                // dropped fan-out. Log and continue.
                Log::error('BroadcastGroupDeleted.chunk_failed', [
                    'chat_room_id' => $this->groupRoomId,
                    'recipients'   => count($channels),
                    'error'        => $e->getMessage(),
                ]);
            }
        }
    }
}
