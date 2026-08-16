<?php

namespace Modules\Chat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Entities\ChatRoomMember;

/**
 * Fans out a `delete-message` signal for one or more group messages to every
 * active member of the group's room (REALTIME_CHAT_REBUILD_PLAN section 5.4).
 *
 * Twin of the 1:1 DeleteMessage broadcast (Modules\Chat\Events\DeleteMessage):
 * the payload carries ONLY the affected server message ids. The Flutter client
 * (_applyDeleteMessage) flips those existing rows to deletedForAll keyed by the
 * globally-unique server message id — no room resolution needed — so both stacks
 * apply a delete identically and never insert a row.
 *
 * Delivery mirrors BroadcastGroupMessage exactly: active members are resolved
 * from chat_room_members, chunked, and each chunk handed to the active broadcaster
 * as an array of legacy `user-{id}` channels (one Centrifugo /broadcast call per
 * chunk). The default connection follows the realtime_transport flag, so under
 * 'pusher' it publishes to `user-{id}` and under 'centrifugo'/'dual' the same
 * names flow through ChannelMapper -> `user:#{id}`. The actor is excluded from the
 * fan-out (they already applied the delete locally / optimistically).
 */
class BroadcastGroupDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RECIPIENT_CHUNK = 500;

    /**
     * @param  int        $groupRoomId  chat_rooms.id of the group's unified room.
     * @param  int[]      $messageIds   Affected server message ids (soft-deleted).
     * @param  int|null   $actorId      Deleter; excluded from the fan-out.
     * @param  string     $event        broadcastAs-style event the client routes on.
     * @param  int[]|null $recipientIds Active member ids SNAPSHOT at dispatch time
     *                                  (deleter already excluded by the caller). When
     *                                  provided the fan-out delivers to exactly this
     *                                  immutable set (chunked in-memory), giving a
     *                                  recipient set consistent as of the delete and
     *                                  removing the per-chunk live SELECT race. Null
     *                                  keeps the legacy run-time chunkById read.
     */
    public function __construct(
        private int $groupRoomId,
        private array $messageIds,
        private ?int $actorId = null,
        private string $event = 'delete-message',
        private ?array $recipientIds = null,
    ) {
    }

    public function handle(): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map(fn ($id) => (int) $id, $this->messageIds),
            fn ($id) => $id > 0
        )));

        if (empty($ids)) {
            return;
        }

        $broadcaster = Broadcast::connection();
        $payload = ['message_id' => $ids];

        $deliverChunk = function (array $userIds) use ($broadcaster, $payload): void {
            $channels = array_map(fn ($id) => 'user-' . $id, $userIds);
            if (empty($channels)) {
                return;
            }

            try {
                $broadcaster->broadcast($channels, $this->event, $payload);
            } catch (\Throwable $e) {
                // Never abort the remaining chunks; the REST sync layer is the
                // safety net for a dropped fan-out. Log and continue.
                Log::error('BroadcastGroupDelete.chunk_failed', [
                    'chat_room_id' => $this->groupRoomId,
                    'recipients'   => count($channels),
                    'error'        => $e->getMessage(),
                ]);
            }
        };

        // Preferred path: deliver to the recipient set SNAPSHOTTED at dispatch time
        // (consistent as of the delete, no per-chunk live SELECT race). The deleter
        // was already excluded when the snapshot was captured. Falls back to a
        // run-time chunkById read only for callers that did not snapshot.
        if ($this->recipientIds !== null) {
            $userIds = array_values(array_unique(array_filter(
                $this->recipientIds,
                fn ($id) => (int) $id > 0
            )));

            foreach (array_chunk($userIds, self::RECIPIENT_CHUNK) as $chunk) {
                $deliverChunk($chunk);
            }

            return;
        }

        ChatRoomMember::query()
            ->where('chat_room_id', $this->groupRoomId)
            ->where('status', 'active')
            ->when($this->actorId !== null, fn ($q) => $q->where('user_id', '!=', $this->actorId))
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunkById(self::RECIPIENT_CHUNK, function ($members) use ($deliverChunk): void {
                $deliverChunk($members->pluck('user_id')->all());
            });
    }
}
