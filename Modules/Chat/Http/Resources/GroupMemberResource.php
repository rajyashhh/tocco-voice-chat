<?php

namespace Modules\Chat\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Entities\ChatRoomMember;

/**
 * One group member row (plan §5.2). Wraps a chat_room_members record for the
 * group's unified room and exposes the role/status the client needs to render the
 * member list and decide which moderation buttons to *display* (the back end is the
 * real guard — these flags are cosmetic, every action is re-authorized server-side
 * by GroupPolicy/GroupService).
 *
 * The member's User (name/avatar) is eager-loaded by the controller via the `user`
 * relation to avoid N+1 across a member page; when absent the user fields are null.
 */
class GroupMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ChatRoomMember $this */
        $user = $this->relationLoaded('user') ? $this->user : null;

        return [
            'user_id'       => (int) $this->user_id,
            'name'          => $user?->name,
            'avatar'        => $user?->profile?->avatar,
            'role'          => $this->role,
            'status'        => $this->status,
            'muted_until'   => optional($this->muted_until)->toIso8601String(),
            'last_read_seq' => (int) $this->last_read_seq,
            'joined_at'     => optional($this->joined_at)->toIso8601String(),
        ];
    }
}
