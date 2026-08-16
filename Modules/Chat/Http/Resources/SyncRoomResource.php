<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Entities\ChatRoomMember;

/**
 * One row of GET /api/v1/sync/rooms — the per-room sync header the client uses
 * to decide what to gap-fill.
 *
 * Carries the room's authoritative high-water-mark (last_seq) and the caller's
 * own read cursor (my_last_read_seq) so the client can compute its O(1) unread
 * count (last_seq - my_last_read_seq) and recover from my_last_read_seq via the
 * since_seq endpoint after a recovery:false.
 *
 * The current user's ChatRoomMember row is injected as `$this->my_membership`
 * by the controller (already resolved against chat_room_members, with a legacy
 * participant fallback) to avoid a per-row query.
 */
class SyncRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ChatRoomMember|null $membership */
        $membership = $this->my_membership ?? null;

        $myLastReadSeq = $membership ? (int) $membership->last_read_seq : 0;
        $lastSeq       = (int) $this->last_seq;

        $group = $this->relationLoaded('group') ? $this->group : null;

        // For a 1:1 room, resolve the OTHER participant (the peer) so the client
        // can show the conversation's name/avatar and open it. The peer is the
        // participant whose id is not the caller's; mirrors ChatRoomResource.
        $peer = null;
        if (!$group) {
            $callerId = (int) $request->user()->id;
            $peer = ((int) $this->user_id === $callerId) ? $this->userTwo : $this->userOne;
        }

        return [
            'room_id'          => (int) $this->id,
            'type'             => $group ? 'group' : 'dm',
            'last_seq'         => $lastSeq,
            'my_last_read_seq' => $myLastReadSeq,
            'unread_count'     => max(0, $lastSeq - $myLastReadSeq),
            'my_role'          => $membership?->role,
            // 1:1 peer header (null for groups, which carry their own name/avatar
            // under `group`). Lets the drift list render + open a dm row.
            'peer_user_id'     => $peer?->id,
            'title'            => $peer?->name,
            'avatar'           => $peer?->profile?->avatar,
            'muted_until'      => optional($membership?->muted_until)->toIso8601String(),
            'cleared_seq'      => $membership ? (int) $membership->cleared_seq : 0,
            'group'            => $group ? [
                'id'            => (int) $group->id,
                'name'          => $group->name,
                'avatar'        => $group->avatar,
                'owner_id'      => (int) $group->owner_id,
                'members_count' => (int) $group->members_count,
            ] : null,
            'last_message'     => $this->relationLoaded('lastMessage') && $this->lastMessage
                ? new SyncMessageResource($this->lastMessage)
                : null,
            'last_message_at'  => optional($this->last_message_at)->toIso8601String(),
            // updated_at is a pre-formatted, tz-adjusted string via the model's
            // TimestampsWithTimezone accessor — emit as-is (not Carbon).
            'updated_at'       => $this->updated_at,
        ];
    }
}
