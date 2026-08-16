<?php

namespace Modules\Chat\Http\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Exceptions\GroupException;
use Modules\Chat\Jobs\BroadcastGroupDeleted;
use Modules\Chat\Policies\GroupPolicy;

/**
 * Group business logic (plan §5.2 + §5.5). The back end is the real guard:
 * every mutating operation resolves the actor's membership, runs it through
 * GroupPolicy, mutates state atomically, emits the matching system event into the
 * unified chat_messages timeline and audits sensitive actions.
 *
 * Model (plan §5, decision §10#2 "full unification"):
 *   - a group is a chat_rooms row (type='group') + a chat_groups row;
 *   - membership lives in the unified chat_room_members table (role/status);
 *   - messages + system events live in chat_messages keyed by the same
 *     chat_room_id and ordered by the per-room server_seq.
 *
 * members_count is maintained atomically: every membership add/remove that
 * changes the active population is a single conditional UPDATE inside the same
 * transaction as the membership write, so the denormalized counter can never
 * drift under concurrency.
 *
 * This service owns logic only. It never touches routes/controllers/jobs (other
 * agents) and never broadcasts — the caller dispatches the fan-out job after a
 * successful call. It uses NextServerSeqService (via GroupSystemEventService) for
 * the server_seq of every system event.
 */
class GroupService
{
    public function __construct(
        protected GroupPolicy $policy,
        protected GroupSystemEventService $events,
    ) {
    }

    /**
     * Create a group: one chat_rooms row (type='group'), the chat_groups row and
     * the owner's chat_room_members row, atomically. members_count starts at 1
     * (the owner). Emits no system event — the room is born with its owner.
     *
     * @param  array<string,mixed>  $meta  name (required), description, avatar,
     *                                     privacy, join_policy, max_members,
     *                                     only_admins_post.
     */
    public function createGroup(User $owner, array $meta): ChatGroup
    {
        $name = trim((string) ($meta['name'] ?? ''));
        if ($name === '') {
            throw GroupException::invalid('group name is required');
        }

        $maxMembers = (int) ($meta['max_members'] ?? 256);
        if ($maxMembers < 2) {
            throw GroupException::invalid('max_members must be at least 2');
        }

        return DB::transaction(function () use ($owner, $meta, $name, $maxMembers) {
            $room = ChatRoom::create([
                'user_id'  => $owner->id,
                'user_id2' => null,
                'type'     => 'group',
                'last_seq' => 0,
            ]);

            $group = ChatGroup::create([
                'chat_room_id'     => $room->id,
                'name'             => $name,
                'description'      => $meta['description'] ?? null,
                'avatar'           => $meta['avatar'] ?? null,
                'owner_id'         => $owner->id,
                'privacy'          => $meta['privacy'] ?? 'private',
                'join_policy'      => $meta['join_policy'] ?? 'invite_only',
                'invite_token'     => $this->generateInviteToken(),
                'members_count'    => 1,
                'max_members'      => $maxMembers,
                'only_admins_post' => (bool) ($meta['only_admins_post'] ?? false),
            ]);

            ChatRoomMember::create([
                'chat_room_id' => $room->id,
                'user_id'      => $owner->id,
                'role'         => 'owner',
                'status'       => 'active',
                'joined_at'    => now(),
            ]);

            return $group;
        });
    }

    /**
     * Add one or more users as active members. owner/admin only (policy). Skips
     * users that are already active members; revives a previously left/banned row
     * back to active (re-adding a member who left). Enforces max_members against
     * the live active count. Emits one member_joined system event per newly added
     * user and audits the batch.
     *
     * @param  array<int>  $userIds
     * @return array<int>  The user ids actually added (or revived).
     */
    public function addMembers(User $actor, ChatGroup $group, array $userIds): array
    {
        $actorMembership = $this->resolveMembership($group, $actor->id);

        if (!$this->policy->addMembers($actor, $group, $actorMembership)) {
            throw GroupException::forbidden();
        }

        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $userIds = array_filter($userIds, fn ($id) => $id > 0);

        if (empty($userIds)) {
            return [];
        }

        $added = DB::transaction(function () use ($group, $userIds, $actor) {
            $group = ChatGroup::lockForUpdate()->find($group->id);

            $existing = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                ->whereIn('user_id', $userIds)
                ->get()
                ->keyBy('user_id');

            // Live active count under the row lock — only used to gate max_members
            // at decision time. The counter is moved by a relative delta below, so
            // this snapshot never becomes the persisted value (no drift on a race
            // with a concurrent add/remove that committed before our lock).
            $activeCount = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                ->where('status', 'active')
                ->count();

            $added = [];

            foreach ($userIds as $userId) {
                $member = $existing->get($userId);

                if ($member && $member->status === 'active') {
                    continue; // already in
                }

                if ($activeCount >= $group->max_members) {
                    throw GroupException::conflict('group is full');
                }

                if ($member) {
                    $member->forceFill([
                        'role'       => $member->role === 'owner' ? 'owner' : 'member',
                        'status'     => 'active',
                        'invited_by' => $actor->id,
                        'joined_at'  => now(),
                        'left_at'    => null,
                    ])->save();
                } else {
                    ChatRoomMember::create([
                        'chat_room_id' => $group->chat_room_id,
                        'user_id'      => $userId,
                        'role'         => 'member',
                        'status'       => 'active',
                        'invited_by'   => $actor->id,
                        'joined_at'    => now(),
                    ]);
                }

                $activeCount++;
                $added[] = $userId;
            }

            if (!empty($added)) {
                $this->adjustMembersCount($group, count($added));
            }

            return $added;
        });

        if (!empty($added)) {
            // ONE batched system event for the whole bulk add — not one per member.
            // The per-member loop made adding N users to an M-member group cost N
            // seq-allocating transactions, N timeline rows AND N full fan-outs (each
            // O(M) -> N×M deliveries): a self-inflicted storm at scale (1k added to a
            // 100k group ≈ 100M deliveries). record() already emits exactly one
            // seq/transaction/fan-out, so a single members_joined row carrying the
            // user_ids[] array turns N×M into 1×M. The client renders "N members
            // joined" from the array (mirrors the already-batched audit below).
            $this->events->record($group, GroupSystemEventService::MEMBERS_JOINED, [
                'user_ids'   => $added,
                'invited_by' => $actor->id,
            ], $actor->id);

            $this->events->audit($group, GroupSystemEventService::AUDIT_ADD_MEMBER, $actor->id, null, [
                'user_ids' => $added,
            ]);
        }

        return $added;
    }

    /**
     * Kick (remove) a member. owner: anyone but self; admin: members only — never
     * another admin or the owner (policy). The target's row is set to status=left
     * (history preserved per plan §5.1), members_count decremented atomically.
     * Emits member_kicked + audits.
     */
    public function removeMember(User $actor, ChatGroup $group, int $targetUserId): void
    {
        $actorMembership  = $this->resolveMembership($group, $actor->id);
        $targetMembership = $this->resolveMembership($group, $targetUserId);

        if (!$this->policy->kick($actor, $group, $actorMembership, $targetMembership)) {
            throw GroupException::forbidden();
        }

        DB::transaction(function () use ($group, $targetMembership) {
            $this->deactivateMembership($targetMembership, 'left');
            $this->adjustMembersCount($group, -1);
        });

        $this->events->record($group, GroupSystemEventService::MEMBER_KICKED, [
            'user_id' => $targetUserId,
            'by'      => $actor->id,
        ], $actor->id);

        $this->events->audit($group, GroupSystemEventService::AUDIT_KICK, $actor->id, $targetUserId);
    }

    /**
     * Mute a member until $until (null => indefinite). owner/admin authority,
     * admin cannot mute admin/owner (policy). Sets status=muted + muted_until;
     * does NOT change members_count (the user stays a member, just can't post).
     * Emits member_muted + audits.
     */
    public function muteMember(User $actor, ChatGroup $group, int $targetUserId, ?\DateTimeInterface $until = null): void
    {
        $actorMembership  = $this->resolveMembership($group, $actor->id);
        $targetMembership = $this->resolveMembership($group, $targetUserId);

        if (!$this->policy->mute($actor, $group, $actorMembership, $targetMembership)) {
            throw GroupException::forbidden();
        }

        $targetMembership->forceFill([
            'status'      => 'muted',
            'muted_until' => $until,
        ])->save();

        $this->events->record($group, GroupSystemEventService::MEMBER_MUTED, [
            'user_id'     => $targetUserId,
            'by'          => $actor->id,
            'muted_until' => $until?->format(DATE_ATOM),
        ], $actor->id);

        $this->events->audit($group, GroupSystemEventService::AUDIT_MUTE, $actor->id, $targetUserId, [
            'muted_until' => $until?->format(DATE_ATOM),
        ]);
    }

    /**
     * Promote a plain active member to admin. owner only (policy).
     * Emits member_promoted + audits.
     */
    public function promote(User $actor, ChatGroup $group, int $targetUserId): void
    {
        $actorMembership  = $this->resolveMembership($group, $actor->id);
        $targetMembership = $this->resolveMembership($group, $targetUserId);

        if (!$this->policy->promote($actor, $group, $actorMembership, $targetMembership)) {
            throw GroupException::forbidden();
        }

        $targetMembership->forceFill(['role' => 'admin'])->save();

        $this->events->record($group, GroupSystemEventService::MEMBER_PROMOTED, [
            'user_id' => $targetUserId,
            'by'      => $actor->id,
        ], $actor->id);

        $this->events->audit($group, GroupSystemEventService::AUDIT_PROMOTE, $actor->id, $targetUserId);
    }

    /**
     * Demote an admin back to member. owner only (policy).
     * Emits member_demoted + audits.
     */
    public function demote(User $actor, ChatGroup $group, int $targetUserId): void
    {
        $actorMembership  = $this->resolveMembership($group, $actor->id);
        $targetMembership = $this->resolveMembership($group, $targetUserId);

        if (!$this->policy->demote($actor, $group, $actorMembership, $targetMembership)) {
            throw GroupException::forbidden();
        }

        $targetMembership->forceFill(['role' => 'member'])->save();

        $this->events->record($group, GroupSystemEventService::MEMBER_DEMOTED, [
            'user_id' => $targetUserId,
            'by'      => $actor->id,
        ], $actor->id);

        $this->events->audit($group, GroupSystemEventService::AUDIT_DEMOTE, $actor->id, $targetUserId);
    }

    /**
     * Leave the group voluntarily. Any participating member may leave EXCEPT the
     * owner, who must transfer ownership (or delete the group) first — leaving an
     * ownerless group is forbidden. Sets status=left, decrements members_count,
     * emits member_left (no audit — self-service, not moderation).
     */
    public function leave(User $actor, ChatGroup $group): void
    {
        $membership = $this->resolveMembership($group, $actor->id);

        if (!$membership || !in_array($membership->status, ['active', 'muted'], true)) {
            throw GroupException::forbidden('not a member');
        }

        if ($membership->role === 'owner') {
            throw GroupException::conflict('owner must transfer ownership or delete the group before leaving');
        }

        DB::transaction(function () use ($group, $membership) {
            $this->deactivateMembership($membership, 'left');
            $this->adjustMembersCount($group, -1);
        });

        $this->events->record($group, GroupSystemEventService::MEMBER_LEFT, [
            'user_id' => $actor->id,
        ], $actor->id);
    }

    /**
     * Transfer ownership to an existing active member. owner only (policy). The
     * new owner becomes role=owner, the old owner steps down to admin (stays in
     * the group). chat_groups.owner_id is updated in the same transaction so the
     * row and the membership roles never disagree. Emits owner_transferred +
     * audits.
     */
    public function transferOwnership(User $actor, ChatGroup $group, int $newOwnerUserId): void
    {
        $actorMembership = $this->resolveMembership($group, $actor->id);

        if (!$this->policy->transferOwnership($actor, $group, $actorMembership)) {
            throw GroupException::forbidden();
        }

        if ($newOwnerUserId === (int) $actor->id) {
            throw GroupException::invalid('already the owner');
        }

        $newOwnerMembership = $this->resolveMembership($group, $newOwnerUserId);

        if (!$newOwnerMembership || !in_array($newOwnerMembership->status, ['active', 'muted'], true)) {
            throw GroupException::invalid('new owner must be an active member');
        }

        DB::transaction(function () use ($group, $actor, $newOwnerUserId) {
            // Lock the group row and re-read both membership rows INSIDE the
            // transaction so the role flip is decided on freshly-locked state, not
            // the snapshot taken before the policy check. Two concurrent transfers
            // serialize on this lock: the second sees the actor is no longer the
            // owner (role flipped to admin by the first) and aborts, so the group
            // can never end up with two owner rows.
            $group = ChatGroup::lockForUpdate()->find($group->id);

            $actorMembership = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                ->where('user_id', $actor->id)
                ->lockForUpdate()
                ->first();

            if (!$actorMembership || $actorMembership->role !== 'owner') {
                throw GroupException::forbidden();
            }

            $newOwnerMembership = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                ->where('user_id', $newOwnerUserId)
                ->lockForUpdate()
                ->first();

            if (!$newOwnerMembership || !in_array($newOwnerMembership->status, ['active', 'muted'], true)) {
                throw GroupException::invalid('new owner must be an active member');
            }

            $newOwnerMembership->forceFill(['role' => 'owner', 'status' => 'active', 'muted_until' => null])->save();
            $actorMembership->forceFill(['role' => 'admin'])->save();

            ChatGroup::where('id', $group->id)->update(['owner_id' => $newOwnerUserId]);
            $group->owner_id = $newOwnerUserId;
        });

        $this->events->record($group, GroupSystemEventService::OWNER_TRANSFERRED, [
            'from' => $actor->id,
            'to'   => $newOwnerUserId,
        ], $actor->id);

        $this->events->audit($group, GroupSystemEventService::AUDIT_TRANSFER_OWNER, $actor->id, $newOwnerUserId);
    }

    /**
     * Update group metadata. owner/admin (policy). Only whitelisted keys are
     * writable here (name/description/avatar/privacy/join_policy/max_members/
     * only_admins_post). Renames and avatar changes additionally emit the matching
     * system event so the change is visible in the timeline. Always audits as
     * edit_meta with the changed keys.
     *
     * @param  array<string,mixed>  $changes
     */
    public function updateGroupMeta(User $actor, ChatGroup $group, array $changes): ChatGroup
    {
        $actorMembership = $this->resolveMembership($group, $actor->id);

        if (!$this->policy->updateMeta($actor, $group, $actorMembership)) {
            throw GroupException::forbidden();
        }

        $allowed = ['name', 'description', 'avatar', 'privacy', 'join_policy', 'max_members', 'only_admins_post'];
        $dirty   = [];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $changes)) {
                continue;
            }
            $value = $changes[$key];

            if ($key === 'name') {
                $value = trim((string) $value);
                if ($value === '') {
                    throw GroupException::invalid('group name cannot be empty');
                }
            }
            if ($key === 'max_members') {
                $value = (int) $value;
                // Validate against the LIVE active count, not the denormalized
                // members_count column: the counter is eventually-consistent under
                // concurrent membership churn, and lowering the cap is a hard
                // safety check that must read ground truth so we never set a cap
                // below the number of members actually in the room.
                $liveActive = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                    ->where('status', 'active')
                    ->count();
                if ($value < $liveActive) {
                    throw GroupException::invalid('max_members cannot be below the current member count');
                }
            }
            if ($key === 'only_admins_post') {
                $value = (bool) $value;
            }
            if ($key === 'privacy') {
                // Privacy is one-way: a private group may be opened to public, but
                // a public group can never be made private again (its history has
                // already been discoverable). Reject the downgrade outright.
                if ($value === 'private' && $group->privacy === 'public') {
                    throw GroupException::invalid('a public group cannot be made private');
                }
            }

            $dirty[$key] = $value;
        }

        if (empty($dirty)) {
            return $group;
        }

        $renamedFrom = $group->name;
        $group->forceFill($dirty)->save();

        if (array_key_exists('name', $dirty) && $dirty['name'] !== $renamedFrom) {
            $this->events->record($group, GroupSystemEventService::GROUP_RENAMED, [
                'from' => $renamedFrom,
                'to'   => $dirty['name'],
                'by'   => $actor->id,
            ], $actor->id);
        }

        if (array_key_exists('avatar', $dirty)) {
            $this->events->record($group, GroupSystemEventService::AVATAR_CHANGED, [
                'by' => $actor->id,
            ], $actor->id);
        }

        $this->events->audit($group, GroupSystemEventService::AUDIT_EDIT_META, $actor->id, null, [
            'changed' => array_keys($dirty),
        ]);

        return $group;
    }

    /**
     * Soft-delete the group. owner only (policy). True teardown so the group
     * disappears for ALL members, not just the actor:
     *  - the chat_groups row is soft deleted (deleted_at);
     *  - every still-participating membership (active/muted) is set status='left'
     *    in the SAME transaction, so the room stops being returned by
     *    GET /api/v1/sync/rooms for anyone (the membership row, not the legacy
     *    participant columns, is the access boundary there) — otherwise the room
     *    would re-materialize on the next sync as a broken type='dm' header
     *    (its `group` relation now resolves to null);
     *  - a `group_deleted` realtime signal is fanned out to the captured member
     *    ids so connected clients drop the drift room live (offline members pick
     *    the removal up via the sync exclusion above).
     *
     * Audits as delete_group. Emits no timeline system event (the conversation is
     * gone for everyone — there is nothing left to render the event in).
     */
    public function deleteGroup(User $actor, ChatGroup $group): void
    {
        $actorMembership = $this->resolveMembership($group, $actor->id);

        if (!$this->policy->deleteGroup($actor, $group, $actorMembership)) {
            throw GroupException::forbidden();
        }

        $roomId = (int) $group->chat_room_id;

        // Capture the recipients BEFORE the teardown — once memberships flip to
        // 'left' the fan-out job can no longer resolve them from the room.
        $memberIds = ChatRoomMember::where('chat_room_id', $roomId)
            ->whereIn('status', ['active', 'muted'])
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($group, $roomId) {
            ChatRoomMember::where('chat_room_id', $roomId)
                ->whereIn('status', ['active', 'muted'])
                ->update([
                    'status'  => 'left',
                    'left_at' => now(),
                ]);

            $group->forceFill(['members_count' => 0])->save();
            $group->delete(); // soft delete (deleted_at)
        });

        $this->events->audit($group, GroupSystemEventService::AUDIT_DELETE_GROUP, $actor->id);

        if (!empty($memberIds)) {
            // Unified chat fan-out policy: dedicated realtimeFanout queue (isolated
            // from the shared heavyProcessing pool). memberIds were already
            // snapshotted above before the teardown flipped them to 'left'.
            dispatchChatFanOut(new BroadcastGroupDeleted($roomId, $memberIds));
        }
    }

    /**
     * Join a group via its invite token. The token must match a live group whose
     * join_policy permits invite-link entry (open or invite_only — 'approval'
     * groups require an approval flow handled by the controller layer, not a
     * direct join). Enforces max_members, idempotent for an already-active member.
     * Emits member_joined.
     */
    /**
     * Join a PUBLIC group by id (chat rebuild §4 — discovery/browse). Only public
     * groups are joinable this way; the join_policy still governs admission:
     *  - open        -> joined immediately
     *  - approval    -> rejected for now (request-to-join flow is a later phase)
     *  - invite_only -> rejected (must use an invite token)
     * Idempotent: an already-active member just gets the group back.
     */
    public function joinPublic(User $actor, ChatGroup $group): ChatGroup
    {
        if ($group->privacy !== 'public') {
            throw GroupException::forbidden('this group is private');
        }
        if ($group->join_policy === 'invite_only') {
            throw GroupException::forbidden('this group is invite-only');
        }
        if ($group->join_policy === 'approval') {
            throw GroupException::forbidden('this group requires approval to join');
        }

        $joined = DB::transaction(function () use ($group, $actor) {
            $group = ChatGroup::lockForUpdate()->find($group->id);

            $existing = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                ->where('user_id', $actor->id)
                ->first();

            if ($existing && $existing->status === 'active') {
                return false; // already a member — idempotent
            }

            $activeCount = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                ->where('status', 'active')
                ->count();

            if ($activeCount >= $group->max_members) {
                throw GroupException::conflict('group is full');
            }

            if ($existing) {
                $existing->forceFill([
                    'role'      => $existing->role === 'owner' ? 'owner' : 'member',
                    'status'    => 'active',
                    'joined_at' => now(),
                    'left_at'   => null,
                ])->save();
            } else {
                ChatRoomMember::create([
                    'chat_room_id' => $group->chat_room_id,
                    'user_id'      => $actor->id,
                    'role'         => 'member',
                    'status'       => 'active',
                    'joined_at'    => now(),
                ]);
            }

            $this->adjustMembersCount($group, 1);

            return true;
        });

        if ($joined) {
            $this->events->record($group, GroupSystemEventService::MEMBER_JOINED, [
                'user_id' => $actor->id,
                'via'     => 'public',
            ], $actor->id);
        }

        return $group;
    }

    public function joinViaInvite(User $actor, string $token): ChatGroup
    {
        $token = trim($token);
        if ($token === '') {
            throw GroupException::invalid('invite token is required');
        }

        $group = ChatGroup::where('invite_token', $token)->first();

        if (!$group) {
            throw GroupException::notFound('invalid invite');
        }

        if ($group->join_policy === 'approval') {
            throw GroupException::forbidden('this group requires approval to join');
        }

        $joined = DB::transaction(function () use ($group, $actor) {
            $group = ChatGroup::lockForUpdate()->find($group->id);

            $existing = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                ->where('user_id', $actor->id)
                ->first();

            if ($existing && $existing->status === 'active') {
                return false; // already a member — idempotent
            }

            $activeCount = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
                ->where('status', 'active')
                ->count();

            if ($activeCount >= $group->max_members) {
                throw GroupException::conflict('group is full');
            }

            if ($existing) {
                $existing->forceFill([
                    'role'      => $existing->role === 'owner' ? 'owner' : 'member',
                    'status'    => 'active',
                    'joined_at' => now(),
                    'left_at'   => null,
                ])->save();
            } else {
                ChatRoomMember::create([
                    'chat_room_id' => $group->chat_room_id,
                    'user_id'      => $actor->id,
                    'role'         => 'member',
                    'status'       => 'active',
                    'joined_at'    => now(),
                ]);
            }

            $this->adjustMembersCount($group, 1);

            return true;
        });

        if ($joined) {
            $this->events->record($group, GroupSystemEventService::MEMBER_JOINED, [
                'user_id' => $actor->id,
                'via'     => 'invite',
            ], $actor->id);
        }

        return $group;
    }

    /**
     * Authorization gate for posting in a group, callable from the message send
     * path (controller/MessageService, other agents) before persisting a group
     * message. Returns the actor's membership so the caller can reuse it; throws
     * GroupException when the actor may not post (not a member, muted, or
     * only_admins_post and not staff).
     */
    public function assertCanPost(User $actor, ChatGroup $group): ChatRoomMember
    {
        $membership = $this->resolveMembership($group, $actor->id);

        if (!$this->policy->postMessage($actor, $group, $membership)) {
            throw GroupException::forbidden('you cannot post in this group');
        }

        return $membership;
    }

    /**
     * Authorization gate for deleting another member's message in a group.
     * Throws when the actor is not owner/admin.
     */
    public function assertCanDeleteOthersMessage(User $actor, ChatGroup $group): void
    {
        $membership = $this->resolveMembership($group, $actor->id);

        if (!$this->policy->deleteOthersMessage($actor, $group, $membership)) {
            throw GroupException::forbidden();
        }

        $this->events->audit($group, GroupSystemEventService::AUDIT_DELETE_MESSAGE, $actor->id);
    }

    /**
     * Soft-delete one or more group messages FOR EVERYONE. Authorization mirrors
     * the chat permission matrix (§5.2):
     *   - a participating member may delete only their OWN messages;
     *   - owner/admin may delete anyone's (GroupPolicy::deleteOthersMessage).
     *
     * Every id must belong to THIS group's room (a cross-room id is rejected, not
     * silently skipped) so the gate can never be bypassed by passing a foreign id.
     * The soft delete sets BOTH user_1_deleted + user_2_deleted (the same
     * convention the 1:1 deleteForAll uses and the client maps to deletedForAll),
     * so on the next sync every member sees the tombstone. Returns the ids actually
     * soft-deleted so the caller can fan out the realtime signal.
     *
     * @param  int[]  $ids
     * @return int[]  the soft-deleted server message ids
     */
    public function deleteGroupMessages(User $actor, ChatGroup $group, array $ids): array
    {
        $membership = $this->resolveMembership($group, $actor->id);

        if (!$this->policy->isReadableMember($membership)) {
            throw GroupException::forbidden();
        }

        $canDeleteOthers = $this->policy->deleteOthersMessage($actor, $group, $membership);

        $roomId = (int) $group->chat_room_id;
        $deleted = [];

        DB::transaction(function () use ($ids, $roomId, $actor, $canDeleteOthers, &$deleted) {
            foreach ($ids as $rawId) {
                $id = (int) $rawId;

                $message = \Modules\Chat\Entities\ChatMessage::where('id', $id)
                    ->where('chat_room_id', $roomId)
                    ->lockForUpdate()
                    ->first();

                // A message that isn't in this group's room (or already gone) is a
                // hard rejection — never a silent skip — so a foreign id cannot be
                // used to probe/act outside the gated room.
                if (!$message) {
                    throw GroupException::invalid('message not found in this group');
                }

                if ((int) $message->user_id !== (int) $actor->id && !$canDeleteOthers) {
                    throw GroupException::forbidden('you cannot delete this message');
                }

                $message->user_1_deleted = now();
                $message->user_2_deleted = now();
                $message->save();

                $deleted[] = (int) $message->id;
            }
        });

        if ($canDeleteOthers && !empty($deleted)) {
            $this->events->audit($group, GroupSystemEventService::AUDIT_DELETE_MESSAGE, $actor->id);
        }

        return $deleted;
    }

    /**
     * Snapshot the active member ids of a group's room as an immutable array, the
     * single recipient-capture seam for every chat fan-out (mirrors the inline
     * snapshot deleteGroup already takes before its teardown). Capturing at dispatch
     * time — instead of letting the fan-out job re-read the live table per chunk —
     * gives a consistent recipient set as of the triggering action and removes the
     * per-chunk SELECT race. Optionally excludes one user (the sender/actor, who
     * already has the change locally).
     *
     * @return int[]
     */
    public function activeMemberIds(ChatGroup $group, ?int $excludeUserId = null): array
    {
        return ChatRoomMember::where('chat_room_id', $group->chat_room_id)
            ->where('status', 'active')
            ->when($excludeUserId !== null, fn ($q) => $q->where('user_id', '!=', $excludeUserId))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    // --- internals --------------------------------------------------------

    /**
     * Load a user's membership row for the group's room (any status), or null.
     */
    private function resolveMembership(ChatGroup $group, int $userId): ?ChatRoomMember
    {
        return ChatRoomMember::where('chat_room_id', $group->chat_room_id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Mark a membership as no longer active (left/banned), recording left_at. Does
     * NOT touch members_count — callers pair this with adjustMembersCount(-1) in
     * the same transaction so the row change and the counter move together.
     */
    private function deactivateMembership(ChatRoomMember $membership, string $status): void
    {
        $membership->forceFill([
            'status'  => $status,
            'left_at' => now(),
        ])->save();
    }

    /**
     * Atomic, never-negative relative move of the denormalized active member
     * counter — the single counter mutator for every membership change. The DB does
     * the arithmetic in one conditional UPDATE (members_count := members_count +
     * delta, floored at 0) so the value is computed from its own current row state,
     * never from a snapshot the caller read earlier. This is what keeps the counter
     * from drifting under concurrency: two transactions that each add/remove a
     * member apply independent +1/-1 deltas that compose correctly even when they
     * interleave, instead of both writing an absolute count that clobbers the other.
     *
     * GREATEST(...,0) is the lower-bound guard for the decrement direction (e.g. a
     * double-kick racing the counter below 0). Every call already runs inside the
     * same transaction as the membership row write it pairs with, so the row change
     * and the counter move commit together or not at all.
     *
     * @param  int  $delta  Signed change (+n added, -n removed).
     */
    private function adjustMembersCount(ChatGroup $group, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        DB::table('chat_groups')
            ->where('id', $group->id)
            ->update([
                'members_count' => DB::raw('GREATEST(CAST(members_count AS SIGNED) + (' . (int) $delta . '), 0)'),
                'updated_at'    => now(),
            ]);

        $group->members_count = max(0, (int) $group->members_count + $delta);
    }

    /**
     * Collision-resistant invite token. The 64-char column + uq_group_invite_token
     * unique index back this; the retry loop covers the astronomically unlikely
     * Str::random clash.
     */
    private function generateInviteToken(): string
    {
        do {
            $token = Str::random(40);
        } while (ChatGroup::where('invite_token', $token)->exists());

        return $token;
    }
}
