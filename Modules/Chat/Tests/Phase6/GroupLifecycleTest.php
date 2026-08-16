<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Exceptions\GroupException;

/**
 * Phase 6 §5.1/§5.3 — group lifecycle + denormalized members_count integrity,
 * through the real GroupService against the production schema.
 *
 * Covers gate item 2:
 *   - createGroup writes chat_rooms(type=group) + chat_groups + the owner's
 *     chat_room_members row, members_count = 1;
 *   - addMembers / removeMember keep members_count in lockstep with the live
 *     active population (the denormalized counter never drifts);
 *   - leave decrements and forbids the owner from leaving;
 *   - transferOwnership moves the role and rewrites chat_groups.owner_id atomically.
 */
class GroupLifecycleTest extends GroupServiceTestCase
{
    public function test_create_group_writes_room_group_and_owner_membership(): void
    {
        $owner = $this->seedUser(1, 'owner');

        $group = $this->service()->createGroup($owner, [
            'name'        => 'Engineering',
            'description' => 'eng chat',
            'max_members' => 50,
        ]);

        // chat_rooms row of type 'group'.
        $room = ChatRoom::find($group->chat_room_id);
        $this->assertNotNull($room);
        $this->assertSame('group', $room->type);

        // chat_groups row.
        $this->assertSame('Engineering', $group->name);
        $this->assertSame(1, (int) $group->owner_id);
        $this->assertSame(50, (int) $group->max_members);
        $this->assertNotEmpty($group->invite_token);

        // owner membership row.
        $ownerRow = $this->membership($group, 1);
        $this->assertNotNull($ownerRow);
        $this->assertSame('owner', $ownerRow->role);
        $this->assertSame('active', $ownerRow->status);

        // members_count starts at exactly the owner.
        $this->assertSame(1, $this->membersCount($group));
        $this->assertSame(1, $this->activeCount($group));
    }

    public function test_create_group_rejects_blank_name_and_tiny_max_members(): void
    {
        $owner = $this->seedUser(1);

        try {
            $this->service()->createGroup($owner, ['name' => '   ']);
            $this->fail('blank name should be rejected');
        } catch (GroupException $e) {
            $this->assertSame(422, $e->getStatus());
        }

        try {
            $this->service()->createGroup($owner, ['name' => 'X', 'max_members' => 1]);
            $this->fail('max_members < 2 should be rejected');
        } catch (GroupException $e) {
            $this->assertSame(422, $e->getStatus());
        }

        // Nothing partially created.
        $this->assertSame(0, ChatGroup::count());
        $this->assertSame(0, ChatRoom::count());
    }

    public function test_add_members_increments_count_and_is_idempotent_for_active(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);

        $this->seedUser(2);
        $this->seedUser(3);

        $added = $this->service()->addMembers($owner, $group, [2, 3]);
        sort($added);
        $this->assertSame([2, 3], $added);
        $this->assertSame(3, $this->membersCount($group));   // owner + 2 + 3
        $this->assertSame(3, $this->activeCount($group));

        // Re-adding active members is a no-op; count is unchanged.
        $again = $this->service()->addMembers($owner, $group, [2, 3]);
        $this->assertSame([], $again);
        $this->assertSame(3, $this->membersCount($group));
    }

    public function test_add_members_revives_a_member_who_left(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);

        $this->seedUser(2);
        $this->service()->addMembers($owner, $group, [2]);
        $this->assertSame(2, $this->membersCount($group));

        // user 2 leaves -> count back to 1.
        $this->service()->leave(User::find(2), $group);
        $this->assertSame(1, $this->membersCount($group));
        $this->assertSame('left', $this->membership($group, 2)->status);

        // re-add the same user -> single row revived to active, count back to 2.
        $added = $this->service()->addMembers($owner, $group, [2]);
        $this->assertSame([2], $added);
        $this->assertSame('active', $this->membership($group, 2)->status);
        $this->assertSame(2, $this->membersCount($group));

        // still exactly ONE membership row for user 2 (revived, not duplicated).
        $rows = ChatRoomMember::where('chat_room_id', $group->chat_room_id)
            ->where('user_id', 2)->count();
        $this->assertSame(1, $rows);
    }

    public function test_add_members_enforces_max_members(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner, ['max_members' => 2]); // owner + 1 more

        $this->seedUser(2);
        $this->seedUser(3);

        // first add fills the group to capacity.
        $this->service()->addMembers($owner, $group, [2]);
        $this->assertSame(2, $this->membersCount($group));

        // adding past capacity is a 409 conflict.
        try {
            $this->service()->addMembers($owner, $group, [3]);
            $this->fail('expected group-full conflict');
        } catch (GroupException $e) {
            $this->assertSame(409, $e->getStatus());
        }

        // count unchanged; user 3 not a member.
        $this->assertSame(2, $this->membersCount($group));
        $this->assertNull($this->membership($group, 3));
    }

    public function test_remove_member_sets_left_and_decrements_count(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'member');
        // arrange a truthful counter for the pre-existing arranged row.
        \Illuminate\Support\Facades\DB::table('chat_groups')->where('id', $group->id)->update(['members_count' => 2]);

        $this->service()->removeMember($owner, $group, 2);

        $row = $this->membership($group, 2);
        $this->assertSame('left', $row->status);
        $this->assertNotNull($row->left_at);
        $this->assertSame(1, $this->membersCount($group));
    }

    public function test_leave_decrements_and_owner_cannot_leave(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->service()->addMembers($owner, $group, [2]);
        $this->assertSame(2, $this->membersCount($group));

        // a normal member can leave.
        $this->service()->leave(User::find(2), $group);
        $this->assertSame('left', $this->membership($group, 2)->status);
        $this->assertSame(1, $this->membersCount($group));

        // the owner cannot leave (must transfer/delete first) -> 409.
        try {
            $this->service()->leave($owner, $group);
            $this->fail('owner should not be able to leave');
        } catch (GroupException $e) {
            $this->assertSame(409, $e->getStatus());
        }
        $this->assertSame('active', $this->membership($group, 1)->status);
        $this->assertSame(1, $this->membersCount($group));
    }

    public function test_transfer_ownership_moves_role_and_rewrites_owner_id(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->service()->addMembers($owner, $group, [2]);

        $this->service()->transferOwnership($owner, $group, 2);

        // new owner row is owner, old owner steps down to admin (still active).
        $this->assertSame('owner', $this->membership($group, 2)->role);
        $this->assertSame('admin', $this->membership($group, 1)->role);
        $this->assertSame('active', $this->membership($group, 1)->status);

        // chat_groups.owner_id rewritten in the same transaction.
        $this->assertSame(2, (int) $group->fresh()->owner_id);

        // population unchanged (both still active).
        $this->assertSame(2, $this->membersCount($group));
    }

    public function test_transfer_ownership_requires_an_active_target_member(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2); // not a member

        try {
            $this->service()->transferOwnership($owner, $group, 2);
            $this->fail('cannot transfer to a non-member');
        } catch (GroupException $e) {
            $this->assertSame(422, $e->getStatus());
        }
        $this->assertSame(1, (int) $group->fresh()->owner_id);
    }
}
