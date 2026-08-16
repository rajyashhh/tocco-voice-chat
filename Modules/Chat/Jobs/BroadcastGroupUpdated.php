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
 * Fans out a `group_updated` signal carrying the group's updated metadata to every
 * active member so connected clients refresh the group header / chats-list row
 * in-place — no cold start (REALTIME_CHAT_REBUILD_PLAN section 5.4 fan-out rules).
 *
 * Twin of BroadcastGroupMessage / BroadcastGroupDeleted: recipients are the active
 * chat_room_members for the group's room, chunked, and each chunk handed to the
 * active broadcaster as an array of legacy `user-{id}` channels (one Centrifugo
 * /broadcast call per chunk). The default connection follows the realtime_transport
 * flag, so 'pusher' publishes to `user-{id}` and 'centrifugo'/'dual' map the same
 * names to `user:#{id}`. The actor is excluded — they already applied the change
 * locally (group_info_screen UpsertGroupLocallyEvent) on the optimistic path.
 *
 * The payload is a SIGNAL, not a chat_messages row: it carries no server_seq and
 * creates no message. It carries only NON-sensitive group metadata (never the
 * invite_token, which is per-role) so the client merges the new name/avatar/
 * privacy/join_policy/only_admins_post/members_count onto its existing group
 * entity. Offline members pick the change up via the REST /sync on next open.
 */
class BroadcastGroupUpdated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RECIPIENT_CHUNK = 500;

    /**
     * @param  int        $groupRoomId  chat_rooms.id of the group's unified room.
     * @param  array      $meta         Non-sensitive group metadata to merge client-side.
     * @param  int|null   $actorId      Editor; excluded from the fan-out.
     * @param  string     $event        broadcastAs-style event the client routes on.
     * @param  int[]|null $recipientIds Active member ids SNAPSHOT at dispatch time;
     *                                  when provided the fan-out delivers to exactly
     *                                  this immutable set (chunked in-memory), removing
     *                                  the run-time chunkById race. Null keeps the legacy
     *                                  live read.
     */
    public function __construct(
        private int $groupRoomId,
        private array $meta,
        private ?int $actorId = null,
        private string $event = 'group_updated',
        private ?array $recipientIds = null,
    ) {
    }

    public function handle(): void
    {
        $broadcaster = Broadcast::connection();
        // Stamp the room id the client keys the in-place update on (twin of the
        // group_deleted payload), alongside the merged meta.
        $payload = array_merge($this->meta, [
            'chat_room_id' => $this->groupRoomId,
            'room_id'      => $this->groupRoomId,
            'type'         => 'group_updated',
        ]);

        $deliverChunk = function (array $userIds) use ($broadcaster, $payload): void {
            $channels = array_map(fn ($id) => 'user-' . $id, $userIds);
            if (empty($channels)) {
                return;
            }

            try {
                $broadcaster->broadcast($channels, $this->event, $payload);
            } catch (\Throwable $e) {
                // Never abort the remaining chunks; the REST /sync layer is the
                // safety net for a dropped fan-out. Log and continue.
                Log::error('BroadcastGroupUpdated.chunk_failed', [
                    'chat_room_id' => $this->groupRoomId,
                    'recipients'   => count($channels),
                    'error'        => $e->getMessage(),
                ]);
            }
        };

        // Preferred path: deliver to the recipient set snapshotted at dispatch time
        // (the actor was already excluded at capture). Falls back to the live read
        // only for callers that did not snapshot.
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
