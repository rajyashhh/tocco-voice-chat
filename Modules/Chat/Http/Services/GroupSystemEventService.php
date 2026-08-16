<?php

namespace Modules\Chat\Http\Services;

use Illuminate\Support\Facades\DB;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatGroupAuditLog;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Jobs\BroadcastGroupMessage;

/**
 * Emits group system events (plan §5.5) as first-class chat messages and records
 * the sensitive ones in the admin audit trail (plan §5.1 chat_group_audit_logs).
 *
 * A system event is a normal row in chat_messages with kind='system', a
 * system_event tag and a system_meta JSON payload — it flows through the SAME
 * server_seq stream, history and fan-out as user messages, so the offline-first
 * client just renders it differently. After the canonical row is committed,
 * record() hands the realtime delivery to the queued BroadcastGroupMessage job via
 * the unified chat fan-out policy (dispatchChatFanOut -> dedicated realtimeFanout
 * queue) — the same seam MessageService::dispatchBroadcast routes group sends
 * through — so connected members receive the event live while the offline-sync REST
 * path remains the safety net. The broadcast NEVER runs in the web request: this
 * service only persists + advances the room pointers (mirroring
 * MessageService::assignSequenceAndDenormalize) and enqueues the fan-out.
 *
 * Recognised system_event values:
 *   member_joined, members_joined (batched bulk add: system_meta.user_ids[]),
 *   member_left, member_kicked, member_promoted, member_demoted, member_muted,
 *   group_renamed, avatar_changed, owner_transferred.
 */
class GroupSystemEventService
{
    public const MEMBER_JOINED     = 'member_joined';
    public const MEMBERS_JOINED    = 'members_joined';
    public const MEMBER_LEFT       = 'member_left';
    public const MEMBER_KICKED     = 'member_kicked';
    public const MEMBER_PROMOTED   = 'member_promoted';
    public const MEMBER_DEMOTED    = 'member_demoted';
    public const MEMBER_MUTED      = 'member_muted';
    public const GROUP_RENAMED     = 'group_renamed';
    public const AVATAR_CHANGED    = 'avatar_changed';
    public const OWNER_TRANSFERRED = 'owner_transferred';

    /**
     * Audit actions written to chat_group_audit_logs for sensitive operations
     * (plan §5.1). Read-only joins/leaves are deliberately NOT audited (high
     * volume, low forensic value); every moderation/structural change is.
     */
    public const AUDIT_KICK            = 'kick';
    public const AUDIT_MUTE            = 'mute';
    public const AUDIT_PROMOTE         = 'promote';
    public const AUDIT_DEMOTE          = 'demote';
    public const AUDIT_DELETE_MESSAGE  = 'delete_message';
    public const AUDIT_EDIT_META       = 'edit_meta';
    public const AUDIT_TRANSFER_OWNER  = 'transfer_owner';
    public const AUDIT_ADD_MEMBER      = 'add_member';
    public const AUDIT_DELETE_GROUP    = 'delete_group';

    public function __construct(protected NextServerSeqService $seq)
    {
    }

    /**
     * Persist a system event into the group's unified timeline.
     *
     * Allocates the room's next atomic server_seq (NextServerSeqService bumps
     * chat_rooms.last_seq) and refreshes last_message_id/last_message_at in one
     * short transaction — never a long lock (the FairLuck 504 lesson). The message
     * is keyed to the actor (user_id) when there is one, so "X promoted Y" renders
     * with X as the source; pure structural events without an actor fall back to
     * the group owner.
     *
     * @param  array<string,mixed>  $meta  Rendered into system_meta (e.g. target id/name).
     * @param  int|null  $actorId  The user who caused the event (null -> owner).
     */
    public function record(ChatGroup $group, string $event, array $meta = [], ?int $actorId = null): ChatMessage
    {
        $roomId   = (int) $group->chat_room_id;
        $authorId = $actorId ?? (int) $group->owner_id;

        $message = DB::transaction(function () use ($roomId, $event, $meta, $authorId) {
            $serverSeq = $this->seq->next($roomId);
            $now       = now();

            $message = ChatMessage::create([
                'chat_room_id' => $roomId,
                'user_id'      => $authorId,
                'client_uuid'  => null,
                'server_seq'   => $serverSeq,
                'kind'         => 'system',
                'system_event' => $event,
                'system_meta'  => $meta,
                'message'      => null,
                'type'         => 'system',
                'status'       => 'sended',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            DB::table('chat_rooms')
                ->where('id', $roomId)
                ->update([
                    'last_message_id' => $message->id,
                    'last_message_at' => $now,
                ]);

            // Advance the ACTOR's own read cursor to the event they just emitted, so
            // the group's unread badge (last_seq - my_last_read_seq) never counts the
            // actor's own system event as unread — mirrors MessageService for user
            // sends. Same short transaction: one indexed, monotonic, parameterized
            // GREATEST UPDATE (GREATEST never rewinds; $serverSeq is bound, not
            // concatenated). firstOrCreate guards the rare row-less actor.
            ChatRoomMember::firstOrCreate(
                ['chat_room_id' => $roomId, 'user_id' => $authorId],
                ['status' => 'active', 'last_read_seq' => 0]
            );

            DB::update(
                'UPDATE chat_room_members
                    SET last_read_seq = GREATEST(CAST(last_read_seq AS SIGNED), ?),
                        updated_at = ?
                  WHERE chat_room_id = ? AND user_id = ?',
                [$serverSeq, $now, $roomId, $authorId]
            );

            return $message;
        });

        // Fan the committed event out to every connected member via the queued job
        // (unified chat fan-out policy -> dedicated realtimeFanout queue, the same
        // seam the group send/update/delete paths route through). No sender exclusion:
        // a system event
        // ("X promoted Y") is shown to the whole room INCLUDING the actor, so the
        // actor's own timeline updates live. Offline members pick it up via REST
        // sync. Dispatched AFTER commit so the broadcast can never race ahead of
        // the durable row.
        $this->dispatchFanOut($roomId, $message);

        return $message;
    }

    /**
     * Enqueue the realtime fan-out of a persisted system-event message. The payload
     * is a self-contained snapshot of the row (the offline-sync ordering/dedup keys
     * + the system_event/system_meta the client renders) — built here rather than
     * through ChatMessageResource because a system event is a domain shape, not a
     * 1:1 user message, and this service must not reach into the HTTP resource layer
     * (owned elsewhere). senderId is null so nobody is excluded from the fan-out.
     */
    private function dispatchFanOut(int $roomId, ChatMessage $message): void
    {
        $payload = [
            'id'           => (int) $message->id,
            'chat_room_id' => $roomId,
            'room_id'      => $roomId,
            'user_id'      => (int) $message->user_id,
            'server_seq'   => (int) $message->server_seq,
            'client_uuid'  => null,
            'kind'         => 'system',
            'system_event' => $message->system_event,
            'system_meta'  => $message->system_meta,
            'message'      => null,
            'type'         => 'system',
            'status'       => $message->status,
            'date_time'    => $message->created_at,
        ];

        // Snapshot the active recipients NOW (no sender exclusion: a system event is
        // shown to the whole room including the actor) so the fan-out delivers to a
        // consistent set as of the event and never re-reads the live member table
        // per chunk. Routed through the unified chat fan-out policy onto the
        // dedicated realtimeFanout queue — isolated from the shared heavyProcessing
        // pool so a gift/lucky burst can never starve chat realtime.
        $recipientIds = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('status', 'active')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        dispatchChatFanOut(
            new BroadcastGroupMessage($roomId, $payload, null, recipientIds: $recipientIds)
        );
    }

    /**
     * Append a sensitive-operation entry to the group's audit trail (plan §5.1).
     * Kept separate from record() because not every system event is auditable and
     * some audited actions (e.g. delete_message, edit_meta) do not emit a timeline
     * system message of their own.
     *
     * @param  array<string,mixed>  $meta
     */
    public function audit(
        ChatGroup $group,
        string $action,
        ?int $actorId,
        ?int $targetId = null,
        array $meta = []
    ): ChatGroupAuditLog {
        return ChatGroupAuditLog::create([
            'group_id'   => $group->id,
            'actor_id'   => $actorId,
            'target_id'  => $targetId,
            'action'     => $action,
            'meta'       => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
