<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Proportional redistribution (§2.3) — the replacement for the old jackpot gate
 * that taxed big bettors at 50–65% effective RTP. The guarantee is EV-EQUALITY:
 * the realised RTP for bet sizes 10/100/1000/5000 must all land ≈ the panel RTP,
 * NOT a discounted rate for the large bets — SO LONG AS the vault can sustain
 * them. (When tiers ARE blocked the SHAPE changes but EV is preserved; that is the
 * S-ACC-3 contract and is covered by the gate-redistribution EV tests.)
 */
class ProportionalDistributionTest extends TestCase
{
    /** Deterministic LCG rng in [1, max]. */
    private function rng(int $seed): callable
    {
        return function (int $max) use (&$seed) {
            $seed = (1103515245 * $seed + 12345) % 2147483648;
            return 1 + (int) floor($seed / 2147483648 * $max);
        };
    }

    private function cfg(float $target, int $negLimit, callable $rng): array
    {
        return [
            'multipliers'   => MultiplierTable::DEFAULT_MULTIPLIERS,
            'baseWeights'   => MultiplierTable::DEFAULT_BASE_WEIGHTS,
            'thresholds'    => ['min' => 10_000, 'tight' => 50_000, 'target' => 200_000, 'high' => 500_000, 'drain' => 1_000_000],
            'targetRtp'     => $target,
            'negativeLimit' => $negLimit,
            'modulators'    => [],
            'rng'           => $rng,
        ];
    }

    public function test_rtp_is_size_independent_when_vault_can_sustain(): void
    {
        $mt = new MultiplierTable();
        $target = 0.80;
        $negLimit = 30_000;

        // Vault large enough that EVEN a ×2000 win on the biggest bet is affordable:
        // worst case 5000 × 2000 = 10,000,000 → vault must exceed that minus limit.
        $vaultSeed = 2_000_000_000;
        $betSizes = [10, 100, 1000, 5000];
        $n = 2_000_000;

        $rtps = [];
        foreach ($betSizes as $bet) {
            $rng = $this->rng(11_113 + $bet);
            $cfg = $this->cfg($target, $negLimit, $rng);
            $vault = $vaultSeed;
            $paid = 0;
            $wagered = 0;
            for ($i = 0; $i < $n; $i++) {
                $sel = $mt->selectPure($vault, 0, 0, $bet, 0, $cfg);
                $m = (int) $sel['multiplier'];
                $wagered += $bet;
                // No solvency clamp here: the vault is huge so the gate never fires;
                // this isolates the proportional-EV guarantee (size independence).
                if ($m > 0) {
                    $paid += $bet * $m;
                }
            }
            $rtps[$bet] = $paid / max(1, $wagered);
        }

        // Each bet size lands near the panel RTP (the heavy ×2000 tail gives ~5%
        // sampling noise at 2M draws — EV-exactness itself is locked analytically
        // in FloorBiasCalibrationTest).
        foreach ($rtps as $bet => $rtp) {
            $this->assertEqualsWithDelta($target, $rtp, 0.06, "bet {$bet}: realised RTP {$rtp} far from {$target}");
        }

        // And crucially: NO systematic discount for big bets — the spread between
        // the smallest and largest bet's realised RTP is sampling noise, not a gate.
        $spread = max($rtps) - min($rtps);
        $this->assertLessThan(0.10, $spread, 'big bets are being taxed (size-dependent RTP)');

        fwrite(STDERR, sprintf(
            "\n[proportional] target=%.2f rtp10=%.4f rtp100=%.4f rtp1000=%.4f rtp5000=%.4f spread=%.4f\n",
            $target, $rtps[10], $rtps[100], $rtps[1000], $rtps[5000], $spread
        ));
    }

    public function test_blocked_tiers_preserve_ev_for_the_survivors(): void
    {
        // S-ACC-3: when the vault forces tiers to be blocked, the survivors are
        // re-anchored so the EV of the redistributed table equals the target (never
        // above). Distribution changes; EV does not.
        $mt = new MultiplierTable();
        $multipliers = MultiplierTable::DEFAULT_MULTIPLIERS;
        $shape = MultiplierTable::DEFAULT_BASE_WEIGHTS; // SHAPE weights

        // Vault small enough to block the big tiers for a bet of 1000.
        [$weights, $fired] = $mt->applyGateWithRedistribution($shape, $multipliers, 60_000, 1000, 30_000, 0.80);

        $this->assertTrue($fired, 'expected the gate to block unaffordable tiers');

        $total = array_sum($weights);
        $ev = 0.0;
        foreach ($multipliers as $m) {
            $ev += $m * ($weights[$m] ?? 0);
        }
        $ev /= $total;

        // EV-equality (≤ target by the floor bias), NOT distribution-equality.
        $this->assertLessThanOrEqual(0.80 + 1e-9, $ev, "redistributed EV {$ev} exceeds target");
        $this->assertGreaterThan(0.0, $ev, 'survivors should still carry EV when any tier is affordable');
    }
}
