<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Adaptive engine (§2.3.1) — MANDATORY test from the design:
 *
 *   "Whatever the modulator values, normalizeWeights re-anchors EV to RTP_eff
 *    exactly (floor-bias ≤ 0). Modulators are injected as weight MULTIPLIERS on the
 *    SHAPE *before* the final calibration, so they can change variance / win timing
 *    (excitement) but can NEVER lift realised RTP above the panel."
 *
 * We measure the realised RTP over many draws across every combination of the four
 * modulators (peak / room / wallet / user) at several strengths. The EXCITEMENT
 * (variance / hit distribution) changes between combos, but the realised RTP must
 * stay anchored to the panel target and NEVER exceed it.
 *
 * Note on the implementation: selectPure injects modulators into the SHAPE, then
 * applyGateWithRedistribution re-anchors to targetRtp — so the EV ceiling is the
 * target regardless of the modulator multipliers. This test guards that contract.
 */
class AdaptiveEngineTest extends TestCase
{
    private function rng(int $seed): callable
    {
        return function (int $max) use (&$seed) {
            $seed = (1103515245 * $seed + 12345) % 2147483648;
            return 1 + (int) floor($seed / 2147483648 * $max);
        };
    }

    /**
     * @return array{rtp:float, hitRate:float}
     */
    private function measure(array $modulators, float $target, int $seed): array
    {
        $mt = new MultiplierTable();
        $negLimit = 30_000;
        // Big vault so the solvency gate never fires — isolate the modulator effect
        // on EV (the gate is covered separately).
        $vault = 2_000_000_000;
        $bet = 100;
        $n = 1_000_000;

        $cfg = [
            'multipliers'   => MultiplierTable::DEFAULT_MULTIPLIERS,
            'baseWeights'   => MultiplierTable::DEFAULT_BASE_WEIGHTS,
            'thresholds'    => ['min' => 10_000, 'tight' => 50_000, 'target' => 200_000, 'high' => 500_000, 'drain' => 1_000_000],
            'targetRtp'     => $target,
            'negativeLimit' => $negLimit,
            'modulators'    => $modulators,
            'rng'           => $this->rng($seed),
        ];

        $paid = 0;
        $wins = 0;
        // userTotalBet above RTP_ACTIVATION so the per-user modulator engages.
        $spent = 5_000_000;
        $received = (int) ($spent * 0.5); // below target → rtpFactor wants to nudge up
        for ($i = 0; $i < $n; $i++) {
            $sel = $mt->selectPure($vault, $spent, $received, $bet, 0, $cfg);
            $m = (int) $sel['multiplier'];
            if ($m > 0) {
                $paid += $bet * $m;
                $wins++;
            }
        }

        return ['rtp' => $paid / ($n * $bet), 'hitRate' => $wins / $n];
    }

    public function test_rtp_constant_across_all_modulator_combinations(): void
    {
        $target = 0.80;

        // Each modulator OFF/ON at a strength. Booleans + ranges are panel values.
        $combos = [
            'all_off' => [],
            'wallet'  => ['wallet_enabled' => true, 'wallet_strength' => 0.40],
            'rtp'     => ['rtp_enabled' => true, 'rtp_strength' => 0.40],
            'peak'    => ['peak_enabled' => true, 'peak_active' => true, 'peak_strength' => 0.50],
            'room'    => ['room_activity_enabled' => true, 'room_activity_factor' => 0.50],
            'wallet+peak' => [
                'wallet_enabled' => true, 'wallet_strength' => 0.40,
                'peak_enabled' => true, 'peak_active' => true, 'peak_strength' => 0.50,
            ],
            'all_on' => [
                'wallet_enabled' => true, 'wallet_strength' => 0.40,
                'rtp_enabled' => true, 'rtp_strength' => 0.40,
                'peak_enabled' => true, 'peak_active' => true, 'peak_strength' => 0.50,
                'room_activity_enabled' => true, 'room_activity_factor' => 0.50,
            ],
            'all_on_strong' => [
                'wallet_enabled' => true, 'wallet_strength' => 0.90,
                'rtp_enabled' => true, 'rtp_strength' => 0.90,
                'peak_enabled' => true, 'peak_active' => true, 'peak_strength' => 0.90,
                'room_activity_enabled' => true, 'room_activity_factor' => 0.90,
            ],
        ];

        $results = [];
        foreach ($combos as $name => $mod) {
            $results[$name] = $this->measure($mod, $target, 424_242);
        }

        $line = "\n[adaptive] target={$target}\n";
        foreach ($results as $name => $r) {
            // THE financial guarantee: realised RTP never exceeds the panel target,
            // for ANY modulator combination (excitement may shift, EV may not rise).
            $this->assertLessThanOrEqual(
                $target + 0.02,
                $r['rtp'],
                "modulator combo '{$name}' lifted realised RTP to {$r['rtp']} (> panel {$target})"
            );
            // And it stays anchored near the target (within tail sampling noise).
            $this->assertEqualsWithDelta(
                $target, $r['rtp'], 0.05,
                "modulator combo '{$name}' RTP {$r['rtp']} drifted off the anchor {$target}"
            );
            $line .= sprintf("  %-16s rtp=%.4f hitRate=%.4f\n", $name, $r['rtp'], $r['hitRate']);
        }
        fwrite(STDERR, $line);

        // Sanity: the EXCITEMENT (hit-rate / shape) actually differs between combos
        // — otherwise the modulators would be no-ops and the test would be vacuous.
        $hitRates = array_map(fn ($r) => $r['hitRate'], $results);
        $this->assertGreaterThan(
            1e-4,
            max($hitRates) - min($hitRates),
            'modulators produced no shape change — the adaptive engine is inert'
        );
    }
}
