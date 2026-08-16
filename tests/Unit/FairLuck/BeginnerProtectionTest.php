<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\BeginnerProtection;
use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Pure tests for BeginnerProtection (§4) — no Laravel, no Redis, no DB.
 */
class BeginnerProtectionTest extends TestCase
{
    // ── eligibility: BOTH conditions (first paid charge AND age window) ──────

    public function test_eligible_only_when_charged_and_young(): void
    {
        $this->assertTrue(BeginnerProtection::isEligible(true, 3, 10));
    }

    public function test_not_eligible_without_paid_charge(): void
    {
        $this->assertFalse(BeginnerProtection::isEligible(false, 3, 10));
    }

    public function test_not_eligible_when_account_too_old(): void
    {
        $this->assertFalse(BeginnerProtection::isEligible(true, 10, 10)); // age==max → out
        $this->assertFalse(BeginnerProtection::isEligible(true, 25, 10));
    }

    public function test_not_eligible_when_window_disabled(): void
    {
        $this->assertFalse(BeginnerProtection::isEligible(true, 0, 0));
    }

    // ── boosted shape: tail (> x100) dropped ────────────────────────────────

    public function test_boosted_shape_drops_tail_above_100(): void
    {
        $shape = BeginnerProtection::boostedShape(
            MultiplierTable::DEFAULT_MULTIPLIERS,      // [2,5,20,100,500,2000]
            MultiplierTable::DEFAULT_BASE_WEIGHTS
        );
        $this->assertSame([2, 5, 20, 100], $shape['multipliers']);
        $this->assertArrayNotHasKey(500, $shape['baseWeights']);
        $this->assertArrayNotHasKey(2000, $shape['baseWeights']);
        $this->assertArrayHasKey(0, $shape['baseWeights']); // 0x preserved
    }

    // ── result pair: boosted/normal payouts computed deterministically ──────

    public function test_draw_pair_payouts_are_bet_times_multiplier(): void
    {
        $mt = new MultiplierTable();
        $normalCfg = [
            'multipliers' => MultiplierTable::DEFAULT_MULTIPLIERS,
            'baseWeights' => MultiplierTable::DEFAULT_BASE_WEIGHTS,
            'thresholds'  => ['min' => 10_000, 'tight' => 50_000, 'target' => 200_000, 'high' => 500_000, 'drain' => 1_000_000],
            'targetRtp'   => 0.80,
            'negativeLimit' => 30_000,
            'modulators'  => [],
            'rng'         => fn (int $max) => 1, // deterministic: lowest roll → 0x
        ];
        $boostedShape = BeginnerProtection::boostedShape(MultiplierTable::DEFAULT_MULTIPLIERS, MultiplierTable::DEFAULT_BASE_WEIGHTS);
        $boostedCfg = $normalCfg;
        $boostedCfg['multipliers'] = $boostedShape['multipliers'];
        $boostedCfg['baseWeights'] = $boostedShape['baseWeights'];
        $boostedCfg['targetRtp'] = 0.92;

        $pair = BeginnerProtection::drawPair($mt, $normalCfg, $boostedCfg, 100, 1_000_000, 0, 0, 0);

        $this->assertSame($pair['normalMult'] * 100, $pair['normalPayout']);
        $this->assertSame($pair['boostedMult'] * 100, $pair['boostedPayout']);
    }
}
