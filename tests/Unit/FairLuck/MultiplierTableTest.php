<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for the selection algorithm — no Laravel, no Redis, no DB.
 * Every method here is exercised with explicit config so it is fully deterministic.
 */
class MultiplierTableTest extends TestCase
{
    private MultiplierTable $mt;

    /** @var array<string,int> */
    private array $thresholds = [
        'min' => 10_000, 'tight' => 50_000, 'target' => 200_000,
        'high' => 500_000, 'drain' => 1_000_000,
    ];

    protected function setUp(): void
    {
        $this->mt = new MultiplierTable();
    }

    // ── walletFactor: bounds, zones, monotonicity ────────────────────────────

    public function test_wallet_factor_is_bounded_minus_one_to_one(): void
    {
        foreach ([-5000, 0, 1, 9999, 10_000, 30_000, 120_000, 350_000, 750_000, 1_000_000, 5_000_000] as $bal) {
            $f = $this->mt->walletFactor($bal, $this->thresholds);
            $this->assertGreaterThanOrEqual(-1.0, $f);
            $this->assertLessThanOrEqual(1.0, $f);
        }
    }

    public function test_wallet_factor_monotonic_non_decreasing(): void
    {
        $prev = -2.0;
        for ($bal = 0; $bal <= 1_200_000; $bal += 5_000) {
            $f = $this->mt->walletFactor($bal, $this->thresholds);
            $this->assertGreaterThanOrEqual($prev - 1e-9, $f, "non-monotonic at {$bal}");
            $prev = $f;
        }
    }

    public function test_negative_and_critical_vault_fully_suppressed(): void
    {
        $this->assertSame(-1.0, $this->mt->walletFactor(-100, $this->thresholds));
        $this->assertSame(-1.0, $this->mt->walletFactor(0, $this->thresholds));
        $this->assertSame(-1.0, $this->mt->walletFactor(10_000, $this->thresholds));
    }

    // ── jackpot gate: solvency guard ─────────────────────────────────────────

    public function test_gate_redistribution_blocks_unaffordable_tiers(): void
    {
        $multipliers = [5, 10, 20, 100, 200, 500, 1000, 2000];
        $shape = array_fill_keys(array_merge([0], $multipliers), 1000.0);
        // Vault = 50,000, bet = 100, negativeLimit = 30,000.
        // A tier M is affordable iff 50000 - 100*M >= -30000  →  M <= 800.
        [$weights, $fired] = $this->mt->applyGateWithRedistribution($shape, $multipliers, 50_000, 100, 30_000, 0.85);

        $this->assertTrue($fired);
        // Blocked tiers are dropped entirely from the redistributed weight set.
        $this->assertArrayNotHasKey(1000, $weights);
        $this->assertArrayNotHasKey(2000, $weights);
        // Affordable tiers survive with positive weight.
        $this->assertGreaterThan(0, $weights[500]);
        $this->assertGreaterThan(0, $weights[200]);
        // EV of the redistributed survivors lands on the target (EV-equality, S-ACC-3).
        $total = array_sum($weights);
        $ev = 0.0;
        foreach ($multipliers as $m) {
            $ev += $m * ($weights[$m] ?? 0);
        }
        $ev /= $total;
        $this->assertLessThanOrEqual(0.85 + 0.001, $ev);
    }

    // ── rtpFactorPure: activation + clamping ─────────────────────────────────

    public function test_rtp_factor_inactive_below_activation(): void
    {
        $sens = $this->sens();
        $this->assertSame(0.0, $this->mt->rtpFactorPure(100, 0.5, $sens, 0.9));
    }

    public function test_rtp_factor_clamped_unit_range(): void
    {
        $sens = $this->sens();
        // huge under-target gap → +1, huge over-target gap → -1
        $this->assertEqualsWithDelta(1.0, $this->mt->rtpFactorPure(10_000, 0.0, $sens, 0.9), 1e-9);
        $this->assertEqualsWithDelta(-1.0, $this->mt->rtpFactorPure(10_000, 5.0, $sens, 0.9), 1e-9);
    }

    // ── selectPure: only configured multipliers (or 0) ever come out ─────────

    public function test_select_only_returns_configured_multipliers_or_zero(): void
    {
        $cfg = $this->cfg();
        $allowed = array_merge([0], $cfg['multipliers']);

        for ($i = 0; $i < 5000; $i++) {
            $r = $this->mt->selectPure(300_000, 0, 0, 100, 0, $cfg);
            $this->assertContains($r['multiplier'], $allowed);
        }
    }

    public function test_forced_win_after_loss_streak_when_affordable(): void
    {
        $cfg = $this->cfg();
        $r = $this->mt->selectPure(300_000, 10_000, 0, 100, 999, $cfg);
        $this->assertTrue($r['forcedWin']);
        $this->assertSame($cfg['sens']['forced_win_mult'], $r['multiplier']);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    private function sens(): array
    {
        return [
            'no_win_factor' => 0.08, 'win_base_factor' => 0.5, 'win_position_factor' => 1.5,
            'boost_base_factor' => 0.3, 'boost_position_factor' => 1.2, 'no_win_floor' => 50_000,
            'wallet_weight' => 0.60, 'rtp_weight' => 0.40, 'rtp_activation' => 500,
            'max_loss_streak' => 20, 'forced_win_mult' => 5,
        ];
    }

    /** @return array<string,mixed> */
    private function cfg(): array
    {
        return [
            'multipliers' => [5, 10, 20, 100, 200, 500, 1000, 2000],
            'baseWeights' => [0 => 93_711, 5 => 4000, 10 => 1500, 20 => 600, 100 => 120, 200 => 45, 500 => 15, 1000 => 6, 2000 => 3],
            'thresholds' => $this->thresholds,
            'sens' => $this->sens(),
            'targetRtp' => 0.99,
            'negativeLimit' => 30_000,
        ];
    }
}
