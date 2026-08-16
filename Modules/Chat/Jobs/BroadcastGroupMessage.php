<?php

namespace Modules\Chat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\Common;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatRoomMember;

/**
 * Fans out a freshly persisted group message to every active member over the
 * realtime transport (REALTIME_CHAT_REBUILD_PLAN section 5.4).
 *
 * Design (mirrors BroadcastChatMessage + the section-5.4 fan-out rules):
 *
 *  - Recipients are the `chat_room_members` rows with status='active' for the
 *    group's unified chat room. The room itself is the canonical key; there is
 *    no per-group channel for message delivery (plan 4.1 / 5.6): every member
 *    receives group messages on their single personal channel `user:#{id}`.
 *
 *  - It does NOT publish per member with one call each (that is the
 *    AllOpeningRoomsZegoRequest anti-pattern). Members are gathered into
 *    chunks and each chunk is handed to the active broadcaster as an array of
 *    legacy `user-{id}` channels. The CentrifugoBroadcaster turns a multi-
 *    channel call into a single Centrifugo `/broadcast` API request (Redis
 *    native fan-out, no per-message quota), so a whole chunk costs one HTTP
 *    call regardless of member count.
 *
 *  - It goes through the existing broadcasting layer via
 *    Broadcast::connection()->broadcast(). The default connection is selected
 *    by the realtime_transport flag (BroadcastServiceProvider::boot ->
 *    broadcasting.default; default 'pusher'). So with the flag at its default
 *    this job publishes to the legacy `user-{id}` Pusher channels exactly like
 *    the 1:1 path, and the Pusher route is never broken. Under 'centrifugo' /
 *    'dual' the same legacy names flow through ChannelMapper -> `user:#{id}`.
 *
 *  - Offline members are not broadcast to individually — they pick the message
 *    up via the REST sync endpoint (history since my_last_read_seq) on next
 *    open (plan 5.4). We still broadcast to every active member because online
 *    presence is not known here; Centrifugo simply has no subscriber on an
 *    offline member's channel, which is a no-op fan-out, not an extra call.
 *
 * It carries only the already-rendered payload (built once with the correct
 * request/locale context, like BroadcastChatMessage) plus the room id and the
 * sender id. Broadcasting runs off the request on the jo-job worker; the write
 * path persists the message + server_seq and returns immediately.
 */
class BroadcastGroupMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Members published per broadcast call. Bounds the channel array (and thus
     * the Centrifugo /broadcast request body) for large groups while keeping the
     * number of HTTP calls minimal: a 256-member group is at most one call.
     */
    private const RECIPIENT_CHUNK = 500;

    /**
     * @param  int        $groupRoomId     chat_rooms.id of the group's unified room.
     * @param  array      $messagePayload  Pre-rendered message payload to deliver.
     * @param  int|null   $senderId        Author; excluded from the fan-out (the
     *                                     sender already has the message locally).
     * @param  string     $event           broadcastAs-style event name the client
     *                                     dispatches on.
     * @param  int[]|null $recipientIds    Active member ids SNAPSHOT at dispatch time.
     *                                     When provided the fan-out delivers to exactly
     *                                     this immutable set (chunked in-memory), giving
     *                                     a consistent recipient set as of the triggering
     *                                     action and removing the per-chunk live SELECT
     *                                     race. Null keeps the legacy run-time chunkById
     *                                     read (used only by callers that cannot snapshot).
     */
    public function __construct(
        private int $groupRoomId,
        private array $messagePayload,
        private ?int $senderId = null,
        private string $event = 'getGroupMessageBloc',
        private ?array $recipientIds = null,
    ) {
    }

    public function handle(): void
    {
        $broadcaster = Broadcast::connection();
        // Resolved once so every chunk pushes the same group identity — without a
        // per-chunk DB lookup. Name feeds the FCM title; name+avatar+id are also
        // injected into the realtime payload (below) so the foreground in-app
        // banner can render the GROUP name as title + the group avatar, and a
        // brand-new group's list row is not blank before the rooms-list sync.
        $group = ChatGroup::where('chat_room_id', $this->groupRoomId)
            ->first(['id', 'name', 'avatar']);
        $groupName = $group?->name ?? '';
        $senderName = $this->resolveSenderName();

        // Inject the conversation (group) identity into the broadcast body. The
        // per-message `user` object (from ChatMessageResource) carries the SENDER;
        // this `group` object carries the ROOM, so the client shows "GroupName"
        // as the banner title and "Sender: message" as the line.
        $this->messagePayload['group'] = [
            'id'    => $group?->id,
            'name'  => $groupName,
            'image' => $group?->avatar,
        ];

        $deliverChunk = function (array $userIds) use ($broadcaster, $groupName, $senderName): void {
            $channels = array_map(fn ($id) => 'user-' . $id, $userIds);

            if (empty($channels)) {
                return;
            }

            try {
                $broadcaster->broadcast($channels, $this->event, $this->messagePayload);
            } catch (\Throwable $e) {
                // Never abort the remaining chunks; the offline-sync layer
                // (REST gap-fill) is the safety net for a dropped fan-out
                // (plan 6.6). Log and continue.
                Log::error('BroadcastGroupMessage.chunk_failed', [
                    'chat_room_id' => $this->groupRoomId,
                    'recipients' => count($channels),
                    'error' => $e->getMessage(),
                ]);
            }

            // FCM push for the same chunk so backgrounded / killed members
            // get a system notification (mirrors the 1:1 sendNotification
            // path). Realtime fan-out handles online members; this covers
            // everyone else.
            $this->pushFcmChunk($userIds, $groupName, $senderName);
        };

        // Preferred path: deliver to the recipient set SNAPSHOTTED at dispatch time
        // (consistent as of the triggering send, no per-chunk live SELECT race). The
        // sender was already excluded when the snapshot was captured. Falls back to a
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
            ->when($this->senderId !== null, fn ($q) => $q->where('user_id', '!=', $this->senderId))
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunkById(self::RECIPIENT_CHUNK, function ($members) use ($deliverChunk): void {
                $deliverChunk($members->pluck('user_id')->all());
            });
    }

    /**
     * Look up the sender's display name once; defaults to empty so the FCM
     * title still renders the group name when the user record can't be loaded.
     */
    private function resolveSenderName(): string
    {
        if ($this->senderId === null) return '';
        return (string) (User::where('id', $this->senderId)->value('name') ?? '');
    }

    /**
     * System push for a chunk of recipients. Reads each member's most recent
     * notification token and fires a single Firebase call per chunk. Wrapped
     * in a try/catch so a misconfigured FCM never breaks the realtime fan-out.
     */
    private function pushFcmChunk(array $userIds, string $groupName, string $senderName): void
    {
        if (empty($userIds)) return;
        try {
            // Notification ID lives on the users table (mirrors
            // MessageRepository::getUserNotificationId for 1:1 — same source).
            // is_logout=1 means the user signed out → don't push to a stale token.
            $tokens = DB::table('users')
                ->whereIn('id', $userIds)
                ->where(function ($q) {
                    $q->whereNull('is_logout')->orWhere('is_logout', '!=', 1);
                })
                ->whereNotNull('notification_id')
                ->where('notification_id', '!=', '')
                ->pluck('notification_id')
                ->unique()
                ->values()
                ->all();
            if (empty($tokens)) return;

            $title = $groupName !== '' ? $groupName : 'رسالة جديدة';
            $body = $senderName !== ''
                ? ($senderName . ': ' . ($this->messagePayload['message'] ?? ''))
                : ($this->messagePayload['message'] ?? '');
            $type = $this->messagePayload['type'] ?? 'text';

            Common::send_firebase_notification(
                $tokens,
                $title,
                $body,
                messageType: $type,
            );
        } catch (\Throwable $e) {
            Log::error('BroadcastGroupMessage.fcm_failed', [
                'chat_room_id' => $this->groupRoomId,
                'recipients' => count($userIds),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
