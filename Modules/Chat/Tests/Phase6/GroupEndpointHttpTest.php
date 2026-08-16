<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Chat\Entities\ChatGroup;

/**
 * Phase 6 gate item 2 — every GroupController endpoint exercised through the REAL
 * controller action with a REAL authenticated user. Proves, per endpoint:
 *
 *   - authorize: a plain member (or non-member) is rejected 403 and nothing
 *     mutates; owner/admin are allowed exactly per the §5.2 matrix;
 *   - the response is the project envelope {success, message, data};
 *   - a GroupException maps to its stable HTTP status (403/404/409/422);
 *   - route-layer guards: unknown id -> findOrFail 404; invalid input -> 422.
 *
 * The back end is the guard: these tests never trust a client role flag — they
 * assert the server-side decision and the resulting state.
 */
class GroupEndpointHttpTest extends GroupEndpointHttpTestCase
{
    // --- store (create) ---------------------------------------------------

    public function test_store_creates_group_and_returns_201_envelope_with_group_data(): void
    {
        $owner = $this->seedUser(1, 'owner');

        $request = $this->request($owner, ['name' => 'HTTP Group', 'max_members' => 10]);
        $res = $this->decode($this->controller()->store($request));

        $this->assertSuccessEnvelope($res, 201);
        $this->assertSame('HTTP Group', $res['body']['data']['name']);
        $this->assertSame(1, (int) $res['body']['data']['owner_id']);
        $this->assertSame('owner', $res['body']['data']['my_role']);
        $this->assertSame(1, (int) $res['body']['data']['members_count']);
        // invite_token exposed to the owner (staff) only.
        $this->assertNotNull($res['body']['data']['invite_token']);

        $this->assertSame(1, ChatGroup::count());
    }

    public function test_store_rejects_blank_name_with_validation_422(): void
    {
        $owner = $this->seedUser(1);
        $request = $this->request($owner, ['name' => '']);

        try {
            $this->controller()->store($request);
            $this->fail('blank name must fail validation');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->status);
        }
        $this->assertSame(0, ChatGroup::count());
    }

    // --- show -------------------------------------------------------------

    public function test_show_returns_group_for_member_and_403_for_non_member(): void
    {
        $owner  = $this->seedUser(1);
        $group  = $this->makeGroup($owner);
        $member = $this->addMemberRow($group, 2, 'member');
        $this->seedUser(99); // stranger

        // member can read.
        $req = $this->request(User::find(2));
        $res = $this->decode($this->controller()->show($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame((int) $group->id, (int) $res['body']['data']['id']);
        $this->assertSame('member', $res['body']['data']['my_role']);
        // invite_token hidden from a plain member.
        $this->assertNull($res['body']['data']['invite_token']);

        // stranger gets a 403 error envelope.
        $req = $this->request(User::find(99));
        $res = $this->decode($this->controller()->show($req, $group->id));
        $this->assertErrorEnvelope($res, 403);
    }

    public function test_show_unknown_group_is_404(): void
    {
        $user = $this->seedUser(1);
        $req  = $this->request($user);

        $this->expectException(ModelNotFoundException::class);
        $this->controller()->show($req, 999999);
    }

    // --- update -----------------------------------------------------------

    public function test_update_allowed_for_admin_forbidden_for_member(): void
    {
        $owner  = $this->seedUser(1);
        $group  = $this->makeGroup($owner, ['name' => 'Orig']);
        $this->addMemberRow($group, 2, 'admin');
        $this->addMemberRow($group, 3, 'member');

        // member -> 403, name unchanged.
        $req = $this->request(User::find(3), ['name' => 'Hacked']);
        $res = $this->decode($this->controller()->update($req, $group->id));
        $this->assertErrorEnvelope($res, 403);
        $this->assertSame('Orig', $group->fresh()->name);

        // admin -> 200, name updated.
        $req = $this->request(User::find(2), ['name' => 'Renamed']);
        $res = $this->decode($this->controller()->update($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame('Renamed', $res['body']['data']['name']);
        $this->assertSame('Renamed', $group->fresh()->name);
    }

    // --- destroy ----------------------------------------------------------

    public function test_destroy_owner_only_member_gets_403(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'admin');

        // admin (not owner) -> 403, not deleted.
        $req = $this->request(User::find(2));
        $res = $this->decode($this->controller()->destroy($req, $group->id));
        $this->assertErrorEnvelope($res, 403);
        $this->assertNull($group->fresh()->deleted_at);

        // owner -> 200, soft-deleted.
        $req = $this->request($owner);
        $res = $this->decode($this->controller()->destroy($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertNotNull(ChatGroup::withTrashed()->find($group->id)->deleted_at);
    }

    // --- addMembers -------------------------------------------------------

    public function test_add_members_admin_allowed_member_forbidden(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'admin');
        $this->addMemberRow($group, 3, 'member');
        $this->seedUser(10);
        $this->seedUser(11);

        // member -> 403, target not added.
        $req = $this->request(User::find(3), ['user_ids' => [10]]);
        $res = $this->decode($this->controller()->addMembers($req, $group->id));
        $this->assertErrorEnvelope($res, 403);
        $this->assertNull($this->membership($group, 10));

        // admin -> 200, returns the added ids in data.
        $req = $this->request(User::find(2), ['user_ids' => [10, 11]]);
        $res = $this->decode($this->controller()->addMembers($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        sort($res['body']['data']['added']);
        $this->assertSame([10, 11], $res['body']['data']['added']);
    }

    public function test_add_members_full_group_returns_409_conflict(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner, ['max_members' => 2]); // owner + 1
        $this->seedUser(2);
        $this->seedUser(3);
        $this->service()->addMembers($owner, $group, [2]); // now full

        $req = $this->request($owner, ['user_ids' => [3]]);
        $res = $this->decode($this->controller()->addMembers($req, $group->id));
        $this->assertErrorEnvelope($res, 409);
    }

    public function test_add_members_validation_requires_user_ids(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);

        $req = $this->request($owner, []); // missing user_ids
        $this->expectException(ValidationException::class);
        $this->controller()->addMembers($req, $group->id);
    }

    // --- removeMember -----------------------------------------------------

    public function test_remove_member_admin_allowed_member_forbidden(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'admin');
        $this->addMemberRow($group, 3, 'member');
        $this->addMemberRow($group, 4, 'member');

        // member -> 403.
        $req = $this->request(User::find(3));
        $res = $this->decode($this->controller()->removeMember($req, $group->id, 4));
        $this->assertErrorEnvelope($res, 403);
        $this->assertSame('active', $this->membership($group, 4)->status);

        // admin -> 200, target left.
        $req = $this->request(User::find(2));
        $res = $this->decode($this->controller()->removeMember($req, $group->id, 4));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame('left', $this->membership($group, 4)->status);
    }

    // --- muteMember -------------------------------------------------------

    public function test_mute_member_admin_allowed_member_forbidden(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'admin');
        $this->addMemberRow($group, 3, 'member');
        $this->addMemberRow($group, 4, 'member');

        $req = $this->request(User::find(3));
        $res = $this->decode($this->controller()->muteMember($req, $group->id, 4));
        $this->assertErrorEnvelope($res, 403);

        $req = $this->request(User::find(2));
        $res = $this->decode($this->controller()->muteMember($req, $group->id, 4));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame('muted', $this->membership($group, 4)->status);
    }

    // --- promote / demote (owner only) -----------------------------------

    public function test_promote_owner_allowed_admin_forbidden(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'admin');
        $this->addMemberRow($group, 3, 'member');

        // admin cannot promote (owner-only) -> 403.
        $req = $this->request(User::find(2));
        $res = $this->decode($this->controller()->promote($req, $group->id, 3));
        $this->assertErrorEnvelope($res, 403);
        $this->assertSame('member', $this->membership($group, 3)->role);

        // owner -> 200.
        $req = $this->request($owner);
        $res = $this->decode($this->controller()->promote($req, $group->id, 3));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame('admin', $this->membership($group, 3)->role);
    }

    public function test_demote_owner_allowed_member_forbidden(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'admin');
        $this->addMemberRow($group, 3, 'member');

        // member cannot demote -> 403.
        $req = $this->request(User::find(3));
        $res = $this->decode($this->controller()->demote($req, $group->id, 2));
        $this->assertErrorEnvelope($res, 403);
        $this->assertSame('admin', $this->membership($group, 2)->role);

        // owner -> 200.
        $req = $this->request($owner);
        $res = $this->decode($this->controller()->demote($req, $group->id, 2));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame('member', $this->membership($group, 2)->role);
    }

    // --- leave ------------------------------------------------------------

    public function test_leave_member_allowed_owner_gets_409(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->service()->addMembers($owner, $group, [2]);

        // member leaves -> 200.
        $req = $this->request(User::find(2));
        $res = $this->decode($this->controller()->leave($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame('left', $this->membership($group, 2)->status);

        // owner cannot leave -> 409.
        $req = $this->request($owner);
        $res = $this->decode($this->controller()->leave($req, $group->id));
        $this->assertErrorEnvelope($res, 409);
        $this->assertSame('active', $this->membership($group, 1)->status);
    }

    // --- transferOwnership ------------------------------------------------

    public function test_transfer_ownership_owner_allowed_member_forbidden(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->service()->addMembers($owner, $group, [2]);

        // member cannot transfer -> 403.
        $req = $this->request(User::find(2), ['user_id' => 2]);
        $res = $this->decode($this->controller()->transferOwnership($req, $group->id));
        $this->assertErrorEnvelope($res, 403);
        $this->assertSame(1, (int) $group->fresh()->owner_id);

        // owner -> 200, owner_id rewritten.
        $req = $this->request($owner, ['user_id' => 2]);
        $res = $this->decode($this->controller()->transferOwnership($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame(2, (int) $group->fresh()->owner_id);
    }

    public function test_transfer_ownership_to_non_member_is_422(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2); // not a member

        $req = $this->request($owner, ['user_id' => 2]);
        $res = $this->decode($this->controller()->transferOwnership($req, $group->id));
        $this->assertErrorEnvelope($res, 422);
    }

    // --- listMembers ------------------------------------------------------

    public function test_list_members_returns_active_members_for_member_403_for_stranger(): void
    {
        $owner = $this->seedUser(1, 'owner');
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'admin');
        $this->addMemberRow($group, 3, 'member');
        $this->addMemberRow($group, 4, 'member', 'left'); // excluded (not active)
        $this->seedUser(99);

        // member can list active members.
        $req = $this->request(User::find(3));
        $res = $this->decode($this->controller()->listMembers($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $userIds = array_column($res['body']['data'], 'user_id');
        sort($userIds);
        $this->assertSame([1, 2, 3], $userIds); // active only; left user excluded
        // ordered owner-first.
        $this->assertSame('owner', $res['body']['data'][0]['role']);

        // stranger -> 403.
        $req = $this->request(User::find(99));
        $res = $this->decode($this->controller()->listMembers($req, $group->id));
        $this->assertErrorEnvelope($res, 403);
    }

    /**
     * N+1 GUARD (gate item 3). listMembers eager-loads `user.profile` because
     * GroupMemberResource renders $user->profile?->avatar; without the eager load
     * each paginated row would fire one extra `users` lookup AND one extra
     * `profiles` lookup, so the DB query count would scale with the page size.
     *
     * We assert the query count is CONSTANT as the member set grows: list a small
     * group, then a much larger one, and prove the second list issues the SAME
     * number of queries as the first. A regression that drops the `.profile` (or
     * the whole `with`) makes the larger page fire extra per-row queries and this
     * test goes red — the exact failure the eager load exists to prevent.
     *
     * Each profile carries a real avatar so the resource actually dereferences
     * $user->profile (a null/missing relation could mask a lazy load).
     */
    public function test_list_members_has_no_n_plus_one_query_count_is_constant_as_members_grow(): void
    {
        $owner = $this->seedUser(1, 'owner');
        $group = $this->makeGroup($owner);
        $this->seedProfile(1);

        // Arrange a SMALL active set (owner + 2 members), each with a profile.
        foreach ([2, 3] as $id) {
            $this->addMemberRow($group, $id, 'member');
            $this->seedProfile($id);
        }

        $smallCount = $this->countListMembersQueries($owner, $group, 100);

        // Arrange a much LARGER active set in the SAME group (+12 more members),
        // each with a profile, so a per-row lazy load would be unmistakable.
        foreach (range(10, 21) as $id) {
            $this->addMemberRow($group, $id, 'member');
            $this->seedProfile($id);
        }

        // Sanity: the page really did grow (15 active rows now: owner + 2 + 12).
        $req  = $this->request($owner, [], ['per_page' => 100]);
        $res  = $this->decode($this->controller()->listMembers($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertCount(15, $res['body']['data'], 'larger page must include every active member');
        // Avatars are resolved from the eager-loaded profile (proves the relation
        // was dereferenced, not skipped).
        $this->assertSame('avatars/u1.png', $res['body']['data'][0]['avatar']);

        $largeCount = $this->countListMembersQueries($owner, $group, 100);

        // The whole point: query count does NOT scale with member count.
        $this->assertSame(
            $smallCount,
            $largeCount,
            "listMembers must not N+1: small set ran {$smallCount} queries, "
            . "larger set ran {$largeCount} (a per-row user/profile lookup would grow this)"
        );
    }

    /**
     * Execute listMembers under the query log and return the number of DB queries
     * it issued. JSON-encodes the resource collection so every per-row lazy load a
     * resource would trigger at serialization time is counted (a lazy
     * profile/avatar dereference fires INSIDE toArray()).
     */
    private function countListMembersQueries(User $actor, ChatGroup $group, int $perPage): int
    {
        $req = $this->request($actor, [], ['per_page' => $perPage]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->controller()->listMembers($req, $group->id);
        $response->getContent(); // force full serialization of the resource page
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    // --- joinViaInvite ----------------------------------------------------

    public function test_join_via_invite_adds_caller_and_invalid_token_is_404(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner, ['join_policy' => 'invite_only']);
        $joiner = $this->seedUser(2);

        // valid token -> 200 joined.
        $req = $this->request($joiner, ['token' => $group->invite_token]);
        $res = $this->decode($this->controller()->joinViaInvite($req));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame('active', $this->membership($group, 2)->status);

        // invalid token -> 404 error envelope.
        $req = $this->request($this->seedUser(3), ['token' => 'definitely-not-a-real-token']);
        $res = $this->decode($this->controller()->joinViaInvite($req));
        $this->assertErrorEnvelope($res, 404);
    }

    public function test_join_via_invite_approval_group_is_403(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner, ['join_policy' => 'approval']);
        $joiner = $this->seedUser(2);

        $req = $this->request($joiner, ['token' => $group->invite_token]);
        $res = $this->decode($this->controller()->joinViaInvite($req));
        $this->assertErrorEnvelope($res, 403);
        $this->assertNull($this->membership($group, 2));
    }

    // --- markRead ---------------------------------------------------------

    public function test_mark_read_advances_cursor_monotonically_for_member(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'member');

        // advance to 5.
        $req = $this->request(User::find(2), ['last_read_seq' => 5]);
        $res = $this->decode($this->controller()->markRead($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame(5, (int) $res['body']['data']['last_read_seq']);

        // a lower value must NOT move the cursor backwards (monotonic high-water).
        $req = $this->request(User::find(2), ['last_read_seq' => 2]);
        $res = $this->decode($this->controller()->markRead($req, $group->id));
        $this->assertSuccessEnvelope($res, 200);
        $this->assertSame(5, (int) $res['body']['data']['last_read_seq']);
        $this->assertSame(5, (int) $this->membership($group, 2)->last_read_seq);
    }

    public function test_mark_read_for_non_member_is_403(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(99);

        $req = $this->request(User::find(99), ['last_read_seq' => 3]);
        $res = $this->decode($this->controller()->markRead($req, $group->id));
        $this->assertErrorEnvelope($res, 403);
    }
}
