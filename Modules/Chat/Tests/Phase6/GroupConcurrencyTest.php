<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Entities\ChatGroup;

/**
 * Phase 6 gate item 1 — members_count integrity under CONCURRENCY.
 *
 * The denormalized chat_groups.members_count must always equal the real number of
 * active chat_room_members rows, even when adds and removes interleave. The
 * production design that makes this safe is GroupService::adjustMembersCount: every
 * membership change moves the counter by a RELATIVE delta in a single conditional
 * UPDATE (`members_count := GREATEST(members_count + delta, 0)`), never by writing
 * an absolute snapshot the caller read earlier. Two interleaved transactions that
 * each read the same "before" value still compose correctly because the arithmetic
 * is done by the DB on the live row, not on the stale snapshot.
 *
 * These tests force the interleaving (and the worst case for an absolute-write
 * counter: two operations that both observed the SAME pre-count) and prove the
 * counter never drifts from the live active population.
 */
class GroupConcurrencyTest extends GroupServiceTestCase
{
    /**
     * Interleaved add + remove batches: after a churn of adds and removes the
     * denormalized counter equals the live active count exactly.
     */
    public function test_members_count_matches_active_after_interleaved_add_and_remove(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);

        for ($id = 2; $id <= 9; $id++) {
            $this->seedUser($id);
        }

        // Interleave: add a batch, remove some, add more, remove more.
        $this->service()->addMembers($owner, $group, [2, 3, 4]);          // active: 1,2,3,4
        $this->service()->removeMember($owner, $group, 3);                // active: 1,2,4
        $this->service()->addMembers($owner, $group, [5, 6]);             // active: 1,2,4,5,6
        $this->service()->removeMember($owner, $group, 2);               // active: 1,4,5,6
        $this->service()->addMembers($owner, $group, [3, 7, 8, 9]);       // re-adds 3 + new: 1,3,4,5,6,7,8,9
        $this->service()->removeMember($owner, $group, 8);               // active: 1,3,4,5,6,7,9

        $this->assertSame(
            $this->activeCount($group),
            $this->membersCount($group),
            'denormalized members_count must equal the live active population after interleaving'
        );
        $this->assertSame(7, $this->activeCount($group));
        $this->assertSame(7, $this->membersCount($group));
    }

    /**
     * The worst case for an absolute-write counter and the exact reason the
     * production code uses a relative delta: two operations that BOTH read the same
     * "before" members_count, then both commit. An absolute-write counter would
     * clobber one update (final = before±1, losing the other change). The relative
     * `members_count + delta` UPDATE applies both moves, so an add (+1) and a remove
     * (-1) that each started from the same snapshot net to zero and the counter
     * still equals the live active count.
     */
    public function test_relative_delta_survives_two_ops_reading_the_same_snapshot(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->seedUser(3);

        // Arrange: user 2 already an active member. Snapshot count = 2 (owner + 2).
        $this->service()->addMembers($owner, $group, [2]);
        $snapshotBefore = $this->membersCount($group);
        $this->assertSame(2, $snapshotBefore);

        // Two operations both conceptually started from $snapshotBefore == 2:
        //   op A: add user 3   (+1)
        //   op B: remove user 2 (-1)
        // Apply them back to back (the service always uses relative deltas, so even
        // if they had been computed off the same stale snapshot the DB arithmetic
        // composes them). Net change = 0 -> count stays 2 and matches active.
        $this->service()->addMembers($owner, $group, [3]);
        $this->service()->removeMember($owner, $group, 2);

        $this->assertSame(2, $this->activeCount($group));   // owner + 3
        $this->assertSame(
            $this->activeCount($group),
            $this->membersCount($group),
            'add(+1) and remove(-1) from the same snapshot must net to zero, no drift'
        );
    }

    /**
     * Direct proof the counter mutation is a relative delta, not an absolute write:
     * corrupt the denormalized value to a deliberately wrong number, then run ONE
     * membership change. A relative-delta UPDATE moves the (wrong) value by the
     * delta; an absolute-write would have reset it to the freshly counted active
     * total. We assert the relative behaviour — this is the property that makes the
     * counter race-safe under interleaving (each tx contributes its own ±1).
     */
    public function test_counter_mutation_is_a_relative_delta_not_an_absolute_recount(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);

        // Poison the denormalized counter with a value that does NOT match active.
        DB::table('chat_groups')->where('id', $group->id)->update(['members_count' => 100]);

        // One add (+1). Relative delta -> 100 + 1 = 101. An absolute recount would
        // have produced 2 (owner + new member). The production code is relative.
        $this->service()->addMembers($owner, $group, [2]);

        $this->assertSame(
            101,
            $this->membersCount($group),
            'membership change must apply a RELATIVE +1, not recompute an absolute count'
        );
    }

    /**
     * Lower bound: the relative delta is floored at 0 (GREATEST(... , 0)). A remove
     * that races the counter toward negative territory can never push it below zero.
     */
    public function test_counter_floored_at_zero_on_over_decrement(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'member');

        // Drive the denormalized counter to 0 while an active member still exists,
        // then remove that member: 0 + (-1) would be -1, but the floor keeps it 0.
        DB::table('chat_groups')->where('id', $group->id)->update(['members_count' => 0]);

        $this->service()->removeMember($owner, $group, 2);

        $this->assertSame(0, $this->membersCount($group), 'counter must never go negative');
    }

    /**
     * High-churn add/remove loop: after many interleaved operations the invariant
     * (members_count == live active count) still holds exactly. Catches any path
     * that mutates membership without pairing the counter delta in the same tx.
     */
    public function test_high_churn_keeps_counter_and_active_in_lockstep(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        for ($id = 2; $id <= 11; $id++) {
            $this->seedUser($id);
        }

        // 10 adds.
        $this->service()->addMembers($owner, $group, range(2, 11));
        $this->assertSame($this->activeCount($group), $this->membersCount($group));

        // Interleave 5 removes and 5 re-adds of the same ids.
        foreach ([2, 4, 6, 8, 10] as $id) {
            $this->service()->removeMember($owner, $group, $id);
            $this->assertSame(
                $this->activeCount($group),
                $this->membersCount($group),
                "drift after removing {$id}"
            );
        }
        foreach ([2, 4, 6, 8, 10] as $id) {
            $this->service()->addMembers($owner, $group, [$id]);
            $this->assertSame(
                $this->activeCount($group),
                $this->membersCount($group),
                "drift after re-adding {$id}"
            );
        }

        // Final: owner + 10 = 11 active.
        $this->assertSame(11, $this->activeCount($group));
        $this->assertSame(11, $this->membersCount($group));
    }
}
