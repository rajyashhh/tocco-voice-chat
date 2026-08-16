<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Floor-bias calibration (S-ACC-4): normalizeWeights uses FLOOR on every win tier
 * and dumps the remainder into the 0x tier, so the ANALYTIC EV of the calibrated
 * table is ALWAYS ≤ targetRtp (in the house's favour) BY CONSTRUCTION — never above.
 * This replaces the deleted RTP_DRIFT_MARGIN safely (no slow bleed).
 *
 * Asserted across profile C and many RTP targets: EV ≤ target, never >.
 */
class FloorBiasCalibrationTest extends TestCase
{
    private MultiplierTable $mt;

    protected function setUp(): void
    {
        $this->mt = new MultiplierTable();
    }

    /** Analytic EV of a calibrated weight table. */
    private function tableEv(array $weights, array $multipliers): float
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            return 0.0;
        }
        $ev = 0.0;
        foreach ($multipliers as $m) {
            $ev += $m * ($weights[$m] ?? 0);
        }
        return $ev / $total;
    }

    public function test_floor_bias_ev_never_exceeds_target_profile_c(): void
    {
        $multipliers = MultiplierTable::DEFAULT_MULTIPLIERS;   // profile C
        $base = MultiplierTable::DEFAULT_BASE_WEIGHTS;

        // Sweep a wide grid of RTP targets.
        for ($rtp = 0.50; $rtp <= 0.95 + 1e-9; $rtp += 0.01) {
            $target = round($rtp, 2);
            $w = $this->mt->normalizeWeights($base, $multipliers, $target);
            $ev = $this->tableEv($w, $multipliers);

            // The whole point of S-ACC-4: floor calibration biases ≤ 0.
            $this->assertLessThanOrEqual(
                $target + 1e-12,
                $ev,
                "FLOOR bias violated: EV {$ev} > target {$target}"
            );
            // And it must be close (calibration is tight, not crushing).
            $this->assertEqualsWithDelta($target, $ev, 0.0005, "EV {$ev} drifted too far below target {$target}");
        }
    }

    public function test_floor_bias_holds_across_owner_receiver_splits(): void
    {
        $multipliers = MultiplierTable::DEFAULT_MULTIPLIERS;
        $base = MultiplierTable::DEFAULT_BASE_WEIGHTS;

        // Several panel splits → RTP_eff derived → table EV must stay ≤ RTP_eff.
        $splits = [[0.01, 0.10], [0.05, 0.10], [0.02, 0.20], [0.10, 0.10], [0.0, 0.0]];
        foreach ($splits as [$owner, $receiver]) {
            foreach ([0.70, 0.80, 0.89] as $panel) {
                $eff = MultiplierTable::effectiveTargetRtpPure($panel, $owner, $receiver, $base, $multipliers);
                $w = $this->mt->normalizeWeights($base, $multipliers, $eff);
                $ev = $this->tableEv($w, $multipliers);
                $this->assertLessThanOrEqual(
                    $eff + 1e-12,
                    $ev,
                    "EV {$ev} > RTP_eff {$eff} for owner={$owner} receiver={$receiver} panel={$panel}"
                );
            }
        }
    }

    public function test_remainder_lands_in_zero_tier_no_coins_minted(): void
    {
        // Sum of all integer weights == round(total × precision): the 0x tier
        // absorbs exactly the floored remainder, so probability mass is conserved.
        $multipliers = MultiplierTable::DEFAULT_MULTIPLIERS;
        $base = MultiplierTable::DEFAULT_BASE_WEIGHTS;
        $precision = 100_000;

        foreach ([0.55, 0.72, 0.80, 0.88] as $target) {
            $w = $this->mt->normalizeWeights($base, $multipliers, $target);
            $totalBase = (float) ($base[0] ?? 0);
            foreach ($multipliers as $m) {
                $totalBase += (float) ($base[$m] ?? 0);
            }
            $this->assertSame((int) round($totalBase * $precision), array_sum($w), "mass not conserved at {$target}");
            $this->assertGreaterThanOrEqual(0, $w[0], '0x tier went negative');
        }
    }
}
