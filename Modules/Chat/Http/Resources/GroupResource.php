<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatRoomMember;

/**
 * A group header (plan §5). Wraps a chat_groups row plus the caller's own
 * membership so the client can render the group and compute its O(1) unread count
 * (room.last_seq - my_last_read_seq) without an extra round-trip.
 *
 * The controller injects the caller's resolved ChatRoomMember as
 * `$this->my_membership` (already loaded against chat_room_members) and eager-loads
 * the `chatRoom` relation for the authoritative last_seq, so this resource issues
 * no per-row query.
 *
 * invite_token is sensitive: it is the join credential, so it is exposed ONLY to a
 * participating owner/admin (the same authority that may add members). Everyone
 * else gets null — the back end never leaks the credential to plain members.
 */
class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ChatGroup $this */
        /** @var ChatRoomMember|null $membership */
        $membership = $this->my_membership ?? null;

        $room    = $this->relationLoaded('chatRoom') ? $this->chatRoom : null;
        $lastSeq = $room ? (int) $room->last_seq : 0;

        $myLastReadSeq = $membership ? (int) $membership->last_read_seq : 0;

        $isStaff = $membership !== null
            && in_array($membership->status, ['active', 'muted'], true)
            && in_array($membership->role, ['owner', 'admin'], true);

        return [
            'id'                => (int) $this->id,
            'chat_room_id'      => (int) $this->chat_room_id,
            'name'              => $this->name,
            'description'       => $this->description,
            'avatar'            => $this->avatar,
            'owner_id'          => (int) $this->owner_id,
            'my_role'           => $membership?->role,
            'members_count'     => (int) $this->members_count,
            'unread_count'      => max(0, $lastSeq - $myLastReadSeq),
            'privacy'           => $this->privacy,
            'join_policy'       => $this->join_policy,
            'only_admins_post'  => (bool) $this->only_admins_post,
            'max_members'       => (int) $this->max_members,
            'muted_until'       => optional($membership?->muted_until)->toIso8601String(),
            'invite_token'      => $isStaff ? $this->invite_token : null,
            'pinned_message_id' => $this->pinned_message_id !== null ? (int) $this->pinned_message_id : null,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
