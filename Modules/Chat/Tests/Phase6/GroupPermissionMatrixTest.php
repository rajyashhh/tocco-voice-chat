<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Exceptions\GroupException;

/**
 * Phase 6 §5.2 — the authoritative group permission matrix, enforced through the
 * REAL GroupService -> GroupPolicy path (the back end is the guard; the Flutter
 * `myRole` is never trusted).
 *
 * For every sensitive operation this proves:
 *   - owner is allowed,
 *   - admin is allowed/denied per the rule,
 *   - a plain member is denied with a 403 GroupException,
 *   - an admin can NOT act on another admin or the owner (moderation outranking).
 *
 * A denied action must RAISE (observable forbidden), never silently no-op — so we
 * assert both the exception AND that no state changed.
 */
class GroupPermissionMatrixTest extends GroupServiceTestCase
{
    private User $owner;
    private ChatGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->seedUser(1, 'owner');
        $this->group = $this->makeGroup($this->owner); // owner row created at members_count=1
    }

    /**
     * Assert a closure throws GroupException with the given HTTP-style status.
     */
    private function assertForbidden(callable $fn, int $status = 403): void
    {
        try {
            $fn();
            $this->fail('Expected GroupException to be thrown, but none was.');
        } catch (GroupException $e) {
            $this->assertSame($status, $e->getStatus(), "expected status {$status}, got {$e->getStatus()}: {$e->getMessage()}");
        }
    }

    // --- KICK ------------------------------------------------------------

    public function test_kick_owner_allowed_admin_allowed_member_forbidden(): void
    {
        $admin  = $this->addMemberRow($this->group, 2, 'admin');
        $member = $this->addMemberRow($this->group, 3, 'member');
        $victim = $this->addMemberRow($this->group, 4, 'member');

        // member cannot kick.
        $this->assertForbidden(fn () => $this->service()->removeMember(User::find(3), $this->group, 4));
        $this->assertSame('active', $this->membership($this->group, 4)->status);

        // admin can kick a plain member.
        $this->service()->removeMember(User::find(2), $this->group, 4);
        $this->assertSame('left', $this->membership($this->group, 4)->status);

        // owner can kick a plain member.
        $this->service()->removeMember($this->owner, $this->group, 3);
        $this->assertSame('left', $this->membership($this->group, 3)->status);
    }

    public function test_admin_cannot_kick_admin_or_owner(): void
    {
        $adminA = $this->addMemberRow($this->group, 2, 'admin');
        $adminB = $this->addMemberRow($this->group, 3, 'admin');

        // admin cannot kick another admin.
        $this->assertForbidden(fn () => $this->service()->removeMember(User::find(2), $this->group, 3));
        $this->assertSame('active', $this->membership($this->group, 3)->status);

        // admin cannot kick the owner.
        $this->assertForbidden(fn () => $this->service()->removeMember(User::find(2), $this->group, 1));
        $this->assertSame('active', $this->membership($this->group, 1)->status);

        // owner CAN kick the admin.
        $this->service()->removeMember($this->owner, $this->group, 2);
        $this->assertSame('left', $this->membership($this->group, 2)->status);
    }

    // --- MUTE ------------------------------------------------------------

    public function test_mute_owner_allowed_admin_allowed_member_forbidden(): void
    {
        $admin  = $this->addMemberRow($this->group, 2, 'admin');
        $member = $this->addMemberRow($this->group, 3, 'member');
        $target = $this->addMemberRow($this->group, 4, 'member');

        // member cannot mute.
        $this->assertForbidden(fn () => $this->service()->muteMember(User::find(3), $this->group, 4));
        $this->assertSame('active', $this->membership($this->group, 4)->status);

        // admin can mute a plain member.
        $this->service()->muteMember(User::find(2), $this->group, 4);
        $this->assertSame('muted', $this->membership($this->group, 4)->status);

        // owner can mute a plain member.
        $this->service()->muteMember($this->owner, $this->group, 3);
        $this->assertSame('muted', $this->membership($this->group, 3)->status);
    }

    public function test_admin_cannot_mute_admin_or_owner(): void
    {
        $this->addMemberRow($this->group, 2, 'admin');
        $this->addMemberRow($this->group, 3, 'admin');

        $this->assertForbidden(fn () => $this->service()->muteMember(User::find(2), $this->group, 3));
        $this->assertSame('active', $this->membership($this->group, 3)->status);

        $this->assertForbidden(fn () => $this->service()->muteMember(User::find(2), $this->group, 1));
        $this->assertSame('active', $this->membership($this->group, 1)->status);
    }

    // --- PROMOTE / DEMOTE (owner only) -----------------------------------

    public function test_promote_owner_only_admin_and_member_forbidden(): void
    {
        $admin  = $this->addMemberRow($this->group, 2, 'admin');
        $member = $this->addMemberRow($this->group, 3, 'member');
        $target = $this->addMemberRow($this->group, 4, 'member');

        // member cannot promote.
        $this->assertForbidden(fn () => $this->service()->promote(User::find(3), $this->group, 4));
        $this->assertSame('member', $this->membership($this->group, 4)->role);

        // admin cannot promote (owner-only).
        $this->assertForbidden(fn () => $this->service()->promote(User::find(2), $this->group, 4));
        $this->assertSame('member', $this->membership($this->group, 4)->role);

        // owner can promote.
        $this->service()->promote($this->owner, $this->group, 4);
        $this->assertSame('admin', $this->membership($this->group, 4)->role);
    }

    public function test_demote_owner_only_admin_and_member_forbidden(): void
    {
        $admin       = $this->addMemberRow($this->group, 2, 'admin');
        $member      = $this->addMemberRow($this->group, 3, 'member');
        $targetAdmin = $this->addMemberRow($this->group, 4, 'admin');

        // member cannot demote.
        $this->assertForbidden(fn () => $this->service()->demote(User::find(3), $this->group, 4));
        $this->assertSame('admin', $this->membership($this->group, 4)->role);

        // admin cannot demote (owner-only).
        $this->assertForbidden(fn () => $this->service()->demote(User::find(2), $this->group, 4));
        $this->assertSame('admin', $this->membership($this->group, 4)->role);

        // owner can demote an admin.
        $this->service()->demote($this->owner, $this->group, 4);
        $this->assertSame('member', $this->membership($this->group, 4)->role);
    }

    // --- TRANSFER OWNERSHIP (owner only) ---------------------------------

    public function test_transfer_ownership_owner_only(): void
    {
        $admin  = $this->addMemberRow($this->group, 2, 'admin');
        $member = $this->addMemberRow($this->group, 3, 'member');

        // admin cannot transfer.
        $this->assertForbidden(fn () => $this->service()->transferOwnership(User::find(2), $this->group, 3));
        $this->assertSame((int) $this->owner->id, (int) $this->group->fresh()->owner_id);

        // member cannot transfer.
        $this->assertForbidden(fn () => $this->service()->transferOwnership(User::find(3), $this->group, 2));
        $this->assertSame((int) $this->owner->id, (int) $this->group->fresh()->owner_id);

        // owner can transfer to an active member.
        $this->service()->transferOwnership($this->owner, $this->group, 2);
        $this->assertSame(2, (int) $this->group->fresh()->owner_id);
    }

    // --- DELETE GROUP (owner only) ---------------------------------------

    public function test_delete_group_owner_only(): void
    {
        $this->addMemberRow($this->group, 2, 'admin');
        $this->addMemberRow($this->group, 3, 'member');

        $this->assertForbidden(fn () => $this->service()->deleteGroup(User::find(2), $this->group));
        $this->assertNull($this->group->fresh()->deleted_at);

        $this->assertForbidden(fn () => $this->service()->deleteGroup(User::find(3), $this->group));
        $this->assertNull($this->group->fresh()->deleted_at);

        $this->service()->deleteGroup($this->owner, $this->group);
        $this->assertNotNull(ChatGroup::withTrashed()->find($this->group->id)->deleted_at);
    }

    // --- EDIT META (owner/admin) -----------------------------------------

    public function test_edit_meta_owner_and_admin_allowed_member_forbidden(): void
    {
        $admin  = $this->addMemberRow($this->group, 2, 'admin');
        $member = $this->addMemberRow($this->group, 3, 'member');

        // member cannot edit.
        $this->assertForbidden(fn () => $this->service()->updateGroupMeta(User::find(3), $this->group, ['name' => 'Hacked']));
        $this->assertNotSame('Hacked', $this->group->fresh()->name);

        // admin can edit.
        $this->service()->updateGroupMeta(User::find(2), $this->group, ['name' => 'AdminEdit']);
        $this->assertSame('AdminEdit', $this->group->fresh()->name);

        // owner can edit.
        $this->service()->updateGroupMeta($this->owner, $this->group, ['name' => 'OwnerEdit']);
        $this->assertSame('OwnerEdit', $this->group->fresh()->name);
    }

    // --- ADD MEMBERS (owner/admin) ---------------------------------------

    public function test_add_members_owner_and_admin_allowed_member_forbidden(): void
    {
        $admin  = $this->addMemberRow($this->group, 2, 'admin');
        $member = $this->addMemberRow($this->group, 3, 'member');
        $this->seedUser(10);
        $this->seedUser(11);

        // plain member cannot add.
        $this->assertForbidden(fn () => $this->service()->addMembers(User::find(3), $this->group, [10]));
        $this->assertNull($this->membership($this->group, 10));

        // admin can add.
        $added = $this->service()->addMembers(User::find(2), $this->group, [10]);
        $this->assertSame([10], $added);

        // owner can add.
        $added = $this->service()->addMembers($this->owner, $this->group, [11]);
        $this->assertSame([11], $added);
    }

    // --- DELETE OTHERS' MESSAGE (owner/admin) ----------------------------

    public function test_delete_others_message_owner_and_admin_allowed_member_forbidden(): void
    {
        $admin  = $this->addMemberRow($this->group, 2, 'admin');
        $member = $this->addMemberRow($this->group, 3, 'member');

        // member cannot delete others' messages.
        $this->assertForbidden(fn () => $this->service()->assertCanDeleteOthersMessage(User::find(3), $this->group));

        // admin + owner may (no exception).
        $this->service()->assertCanDeleteOthersMessage(User::find(2), $this->group);
        $this->service()->assertCanDeleteOthersMessage($this->owner, $this->group);

        $this->assertTrue(true);
    }

    // --- POST MESSAGE (member unless only_admins_post / muted) -----------

    public function test_post_message_blocked_for_muted_and_for_member_when_admins_only(): void
    {
        $member = $this->addMemberRow($this->group, 3, 'member');
        $muted  = $this->addMemberRow($this->group, 4, 'member', 'muted');

        // default: a plain member may post.
        $this->service()->assertCanPost(User::find(3), $this->group);

        // muted member cannot post.
        $this->assertForbidden(fn () => $this->service()->assertCanPost(User::find(4), $this->group));

        // flip only_admins_post: now the plain member cannot post, owner/admin still can.
        $this->service()->updateGroupMeta($this->owner, $this->group, ['only_admins_post' => true]);
        $this->assertForbidden(fn () => $this->service()->assertCanPost(User::find(3), $this->group->fresh()));
        $this->service()->assertCanPost($this->owner, $this->group->fresh());
    }

    // --- NON-MEMBER / banned / left actor revokes everything -------------

    public function test_non_member_and_revoked_actor_is_forbidden_everywhere(): void
    {
        $banned = $this->addMemberRow($this->group, 5, 'admin', 'banned');
        $left   = $this->addMemberRow($this->group, 6, 'admin', 'left');
        $stranger = $this->seedUser(99);
        $victim = $this->addMemberRow($this->group, 3, 'member');

        // banned admin: status revokes the admin authority entirely.
        $this->assertForbidden(fn () => $this->service()->removeMember(User::find(5), $this->group, 3));
        // left admin: same.
        $this->assertForbidden(fn () => $this->service()->removeMember(User::find(6), $this->group, 3));
        // a complete stranger (no membership row).
        $this->assertForbidden(fn () => $this->service()->removeMember(User::find(99), $this->group, 3));

        $this->assertSame('active', $this->membership($this->group, 3)->status);
    }
}
