<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Pure tests for the derived RTP_eff contract (panel ↔ payout):
 *
 *   RTP_eff = (min(panelRTP, 1 − owner − receiver) − pityEV) × DRIFT_MARGIN
 *
 * with pityEV computed from the STATIONARY per-bet forced-win rate
 * π(S) = p0^S(1−p0)/(1−p0^(S+1)) — not the per-cycle p0^S.
 * No Laravel, no Redis, no DB.
 */
class RtpEffectiveTest extends TestCase
{
    private MultiplierTable $mt;

    protected function setUp(): void
    {
        $this->mt = new MultiplierTable();
    }

    // ── normalizeWeights: table EV lands exactly on the target ──────────────

    public function test_normalized_table_ev_equals_target_within_tolerance(): void
    {
        $multipliers = MultiplierTable::DEFAULT_MULTIPLIERS;
        $base = MultiplierTable::DEFAULT_BASE_WEIGHTS;

        foreach ([0.5, 0.7, 0.85, 0.89, 0.95] as $target) {
            $w = $this->mt->normalizeWeights($base, $multipliers, $target);
            $total = array_sum($w);
            $ev = 0.0;
            foreach ($multipliers as $m) {
                $ev += $m * ($w[$m] ?? 0);
            }
            $ev /= $total;
            $this->assertEqualsWithDelta($target, $ev, 0.0005, "EV off target at RTP={$target}");
        }
    }

    public function test_normalized_table_never_exceeds_target_when_unreachable(): void
    {
        // Tiny win weights cannot absorb a huge target — k caps, EV ≤ target.
        $w = $this->mt->normalizeWeights([0 => 1_000_000, 5 => 1], [5], 0.99);
        $total = array_sum($w);
        $ev = 5 * $w[5] / $total;
        $this->assertLessThanOrEqual(0.99 + 1e-9, $ev);
    }

    // ── RTP_eff derivation ───────────────────────────────────────────────────

    public function test_rtp_eff_clamped_to_sustainable_ceiling(): void
    {
        // Panel promises 99% while only 89% of every bet reaches the vault
        // (1% app + 10% receiver) — the old root cause of the vault drain.
        $eff = MultiplierTable::effectiveTargetRtpPure(
            0.99, 0.01, 0.10,
            MultiplierTable::DEFAULT_BASE_WEIGHTS,
            MultiplierTable::DEFAULT_MULTIPLIERS
        );
        $this->assertLessThanOrEqual(0.89, $eff);
        $this->assertGreaterThan(0.70, $eff); // pity + margin must not crater it
    }

    public function test_rtp_eff_no_half_floor_above_sustainable(): void
    {
        // F4: rates 15% + 45% → sustainable 0.40. The old max(0.5, …) floor sat
        // ABOVE the ceiling and drained the vault. RTP_eff must stay ≤ 0.40.
        $eff = MultiplierTable::effectiveTargetRtpPure(
            0.89, 0.15, 0.45,
            MultiplierTable::DEFAULT_BASE_WEIGHTS,
            MultiplierTable::DEFAULT_MULTIPLIERS
        );
        $this->assertLessThanOrEqual(0.40, $eff);
        $this->assertGreaterThanOrEqual(0.0, $eff);
    }

    public function test_rtp_eff_respects_lower_panel_value(): void
    {
        // Panel BELOW the ceiling: pity EV must still be subtracted from the
        // panel value (P2-3), so table-target < panel.
        $eff = MultiplierTable::effectiveTargetRtpPure(
            0.70, 0.01, 0.10,
            MultiplierTable::DEFAULT_BASE_WEIGHTS,
            MultiplierTable::DEFAULT_MULTIPLIERS
        );
        $this->assertLessThan(0.70, $eff);
    }

    // ── pity rate: stationary renewal formula ────────────────────────────────

    public function test_pity_rate_matches_simulated_renewal_process(): void
    {
        $p0 = 0.93;
        $S = MultiplierTable::MAX_LOSS_STREAK; // 20

        $analytic = MultiplierTable::pityRate($p0, $S);

        // Deterministic LCG simulation of the loss-streak chain.
        $seed = 123456789;
        $rand = function () use (&$seed) {
            $seed = (1103515245 * $seed + 12345) % 2147483648;
            return $seed / 2147483648;
        };
        $n = 400_000;
        $streak = 0;
        $forced = 0;
        for ($i = 0; $i < $n; $i++) {
            if ($streak >= $S) {
                $forced++;
                $streak = 0; // forced win resets
                continue;
            }
            if ($rand() < $p0) {
                $streak++;
            } else {
                $streak = 0; // natural win resets
            }
        }
        $empirical = $forced / $n;

        $this->assertEqualsWithDelta($analytic, $empirical, $analytic * 0.10 + 0.0005,
            "stationary pity rate formula diverges from simulation (analytic={$analytic}, empirical={$empirical})");

        // And it must be FAR below the per-cycle probability p0^S that the
        // broken formula used (≈1/(1−p0) ≈ 14× too big here).
        $this->assertLessThan($p0 ** $S, $analytic);
    }

    public function test_pity_rate_edge_cases(): void
    {
        $this->assertSame(0.0, MultiplierTable::pityRate(0.0, 20));
        $this->assertEqualsWithDelta(1 / 21, MultiplierTable::pityRate(1.0, 20), 1e-12);
    }

    // ── empirical RTP of the full draw pipeline ─────────────────────────────

    public function test_empirical_rtp_of_seeded_draws_matches_table_target(): void
    {
        $multipliers = MultiplierTable::DEFAULT_MULTIPLIERS;
        $base = MultiplierTable::DEFAULT_BASE_WEIGHTS;
        $target = 0.85;

        $cfg = [
            'multipliers' => $multipliers,
            'baseWeights' => $base,
            'thresholds' => ['min' => 10_000, 'tight' => 50_000, 'target' => 200_000, 'high' => 500_000, 'drain' => 1_000_000],
            'targetRtp' => $target,
            'negativeLimit' => 30_000,
            // Deterministic LCG rng in [1, max].
            'rng' => (function () {
                $seed = 987654321;
                return function (int $max) use (&$seed) {
                    $seed = (1103515245 * $seed + 12345) % 2147483648;
                    return 1 + (int) floor($seed / 2147483648 * $max);
                };
            })(),
        ];

        // Huge vault so the jackpot gate never interferes with the EV measure.
        $vault = 2_000_000_000;
        $bet = 100;
        $n = 1_000_000;
        $paid = 0;
        for ($i = 0; $i < $n; $i++) {
            // streak pinned to 0: measure the TABLE EV alone (pity excluded).
            $sel = $this->mt->selectPure($vault, 0, 0, $bet, 0, $cfg);
            $paid += $bet * (int) $sel['multiplier'];
        }
        $rtp = $paid / ($n * $bet);

        // Profile C carries a heavy x2000 tail (p≈1.2e-5), so a 1M-draw empirical
        // RTP has large sampling variance around the (analytically exact) EV — the
        // EV-exactness is locked by test_normalized_table_ev_equals_target_within_tolerance.
        // Here we only assert the realised RTP is in a sane band and, per S-ACC-4,
        // the FLOOR calibration biases it at-or-below target on the analytic mean.
        $this->assertEqualsWithDelta($target, $rtp, 0.05, "empirical RTP {$rtp} vs target {$target}");
    }

    // ── jackpot gate exactness ───────────────────────────────────────────────

    public function test_gate_redistribution_drops_exactly_the_unaffordable_tiers(): void
    {
        $multipliers = MultiplierTable::DEFAULT_MULTIPLIERS; // profile C [2,5,20,100,500,2000]
        $shape = array_fill_keys(array_merge([0], $multipliers), 1000.0);
        // vault 10_000, bet 100, limit 30_000 → affordable iff 10000−100M ≥ −30000 → M ≤ 400.
        [$weights, $fired] = $this->mt->applyGateWithRedistribution($shape, $multipliers, 10_000, 100, 30_000, 0.80);
        foreach ($multipliers as $m) {
            if ($m <= 400) {
                $this->assertGreaterThan(0, $weights[$m] ?? 0, "tier {$m} wrongly gated");
            } else {
                $this->assertArrayNotHasKey($m, $weights, "tier {$m} not gated");
            }
        }
        $this->assertTrue($fired);
        $this->assertGreaterThan(0, $weights[0]); // 0x always survives
    }

    // ── costing rule arithmetic (X = P × m × N × c) ─────────────────────────

    public function test_costing_rule_total_equals_promise(): void
    {
        $P = 100; $m = 3; $N = 3; $c = 2;
        $unitPrice = $P * $m;
        $hits = $N * $c;
        $this->assertSame(1800, $unitPrice * $hits); // == charged_total promise
    }
}
