<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatRoomMember;

/**
 * The authoritative group permission matrix (plan §5.2). The back end is the real
 * guard: the Flutter client hides buttons by role for cosmetics only, but every
 * sensitive action is gated here and enforced inside GroupService before any state
 * change. The Flutter `myRole` is never trusted.
 *
 * Authority is the caller's chat_room_members row for the group's room — role
 * (owner|admin|member) AND status (active|muted|banned|left). A non-readable
 * status (banned/left) revokes everything. Muting blocks posting only.
 *
 * The matrix (✓ = allowed):
 *   operation                | owner | admin            | member
 *   -------------------------|-------|------------------|--------------------------
 *   post message             |  ✓    | ✓                | ✓ unless only_admins_post
 *   delete others' message   |  ✓    | ✓                | ✗
 *   kick / mute member       |  ✓    | ✓ (not adm/owner)| ✗
 *   promote / demote admin   |  ✓    | ✗                | ✗
 *   edit group meta          |  ✓    | ✓                | ✗
 *   transfer ownership       |  ✓    | ✗                | ✗
 *   delete group             |  ✓    | ✗                | ✗
 *
 * Each method takes the resolved actor membership (and, where the target matters,
 * the target membership) so the policy is pure and the service resolves rows once.
 * Methods return bool; the service translates a false into a GroupException so the
 * forbidden action is observable, not a silent no-op.
 */
class GroupPolicy
{
    /**
     * Any readable, non-revoked membership may act at all. Banned/left cannot.
     */
    private function isParticipating(?ChatRoomMember $actor): bool
    {
        return $actor !== null
            && in_array($actor->status, ['active', 'muted'], true);
    }

    /**
     * Public predicate for "is a readable (participating) member" — used by the
     * service to gate own-message deletion (a plain member may delete their own
     * message) without exposing the private participation rule.
     */
    public function isReadableMember(?ChatRoomMember $actor): bool
    {
        return $this->isParticipating($actor);
    }

    private function isOwner(?ChatRoomMember $actor): bool
    {
        return $this->isParticipating($actor) && $actor->role === 'owner';
    }

    private function isAdminOrOwner(?ChatRoomMember $actor): bool
    {
        return $this->isParticipating($actor)
            && in_array($actor->role, ['owner', 'admin'], true);
    }

    /**
     * Post a message. Members are blocked when only_admins_post is on, and a muted
     * member (status='muted' or muted_until in the future) is blocked regardless.
     * owner/admin always post.
     */
    public function postMessage(User $user, ChatGroup $group, ?ChatRoomMember $actor): bool
    {
        if (!$this->isParticipating($actor)) {
            return false;
        }

        if ($this->isAdminOrOwner($actor)) {
            return true;
        }

        if ($this->isMuted($actor)) {
            return false;
        }

        return !$group->only_admins_post;
    }

    /**
     * Delete a message authored by someone else. owner/admin only.
     * (Deleting your OWN message is a separate, always-allowed message-ownership
     * path handled by the existing MessageService, not this policy.)
     */
    public function deleteOthersMessage(User $user, ChatGroup $group, ?ChatRoomMember $actor): bool
    {
        return $this->isAdminOrOwner($actor);
    }

    /**
     * Kick a member. owner can kick anyone (except themselves); admin can kick
     * members only — never another admin or the owner.
     */
    public function kick(User $user, ChatGroup $group, ?ChatRoomMember $actor, ?ChatRoomMember $target): bool
    {
        return $this->canModerate($actor, $target);
    }

    /**
     * Mute a member. Same authority as kick (owner: anyone but self; admin:
     * members only).
     */
    public function mute(User $user, ChatGroup $group, ?ChatRoomMember $actor, ?ChatRoomMember $target): bool
    {
        return $this->canModerate($actor, $target);
    }

    /**
     * Promote a member to admin. owner only.
     */
    public function promote(User $user, ChatGroup $group, ?ChatRoomMember $actor, ?ChatRoomMember $target): bool
    {
        return $this->isOwner($actor)
            && $target !== null
            && $target->role === 'member'
            && $this->isParticipating($target);
    }

    /**
     * Demote an admin back to member. owner only.
     */
    public function demote(User $user, ChatGroup $group, ?ChatRoomMember $actor, ?ChatRoomMember $target): bool
    {
        return $this->isOwner($actor)
            && $target !== null
            && $target->role === 'admin'
            && $this->isParticipating($target);
    }

    /**
     * Edit group metadata (name/description/avatar/settings). owner/admin.
     */
    public function updateMeta(User $user, ChatGroup $group, ?ChatRoomMember $actor): bool
    {
        return $this->isAdminOrOwner($actor);
    }

    /**
     * Add members. owner/admin (same authority as editing the group).
     */
    public function addMembers(User $user, ChatGroup $group, ?ChatRoomMember $actor): bool
    {
        return $this->isAdminOrOwner($actor);
    }

    /**
     * Transfer ownership. owner only.
     */
    public function transferOwnership(User $user, ChatGroup $group, ?ChatRoomMember $actor): bool
    {
        return $this->isOwner($actor);
    }

    /**
     * Delete the group permanently (soft delete). owner only.
     */
    public function deleteGroup(User $user, ChatGroup $group, ?ChatRoomMember $actor): bool
    {
        return $this->isOwner($actor);
    }

    /**
     * Shared moderation rule for kick/mute:
     * - actor must be owner or admin (and participating);
     * - actor cannot target themselves through moderation;
     * - an admin may only act on a plain member — never another admin or the owner;
     * - the target must currently be a participating member of the group.
     */
    private function canModerate(?ChatRoomMember $actor, ?ChatRoomMember $target): bool
    {
        if (!$this->isAdminOrOwner($actor) || $target === null) {
            return false;
        }

        if ($actor->user_id === $target->user_id) {
            return false;
        }

        if (!$this->isParticipating($target)) {
            return false;
        }

        // Owner outranks everyone.
        if ($actor->role === 'owner') {
            return true;
        }

        // Admin: only over plain members.
        return $target->role === 'member';
    }

    private function isMuted(ChatRoomMember $actor): bool
    {
        if ($actor->status === 'muted') {
            return true;
        }

        return $actor->muted_until !== null && $actor->muted_until->isFuture();
    }
}
