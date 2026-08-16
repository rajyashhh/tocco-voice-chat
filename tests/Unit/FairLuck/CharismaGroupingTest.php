<?php

namespace Tests\Unit\FairLuck;

use App\Jobs\ProcessLuckyGiftPostJob;
use PHPUnit\Framework\TestCase;

/**
 * Pure tests for the charisma/PK attribution grouping (I3 / RC-2 secondary):
 * each receiver must be attributed EXACTLY their own cut — never the aggregate
 * of the whole send — and the legacy no-map fallback must equal-split, never
 * mint aggregate × N.
 */
class CharismaGroupingTest extends TestCase
{
    public function test_groups_receivers_by_their_own_cut(): void
    {
        $map = [11 => 300, 12 => 300, 13 => 120, 14 => 0];

        $groups = ProcessLuckyGiftPostJob::groupReceiverCuts($map, [11, 12, 13, 14], 720);

        $this->assertSame([120 => [13], 300 => [11, 12]], $groups);

        // Σ over groups == Σ own cuts (no aggregate × N inflation).
        $sum = 0;
        foreach ($groups as $amount => $ids) {
            $sum += $amount * count($ids);
        }
        $this->assertSame(720, $sum);
    }

    public function test_legacy_fallback_equal_splits_never_aggregate_times_n(): void
    {
        $groups = ProcessLuckyGiftPostJob::groupReceiverCuts([], [21, 22, 23], 900);

        $this->assertSame([300 => [21, 22, 23]], $groups);

        $sum = 0;
        foreach ($groups as $amount => $ids) {
            $sum += $amount * count($ids);
        }
        $this->assertLessThanOrEqual(900, $sum); // never more than the aggregate
    }

    public function test_legacy_fallback_sub_unit_share_drops_to_nothing(): void
    {
        $this->assertSame([], ProcessLuckyGiftPostJob::groupReceiverCuts([], [1, 2, 3], 2));
        $this->assertSame([], ProcessLuckyGiftPostJob::groupReceiverCuts([], [], 100));
    }

    public function test_per_group_gate_amounts_of_one_are_skippable(): void
    {
        // F7: the >1 gate must apply per-group, so a seat with cut ≤ 1 never
        // rides a bigger group's dispatch.
        $groups = ProcessLuckyGiftPostJob::groupReceiverCuts([31 => 1, 32 => 500], [31, 32], 501);
        $this->assertArrayHasKey(1, $groups);
        $this->assertArrayHasKey(500, $groups);
        $dispatchable = array_filter(array_keys($groups), fn ($a) => $a > 1);
        $this->assertSame([500], array_values($dispatchable));
    }
}
