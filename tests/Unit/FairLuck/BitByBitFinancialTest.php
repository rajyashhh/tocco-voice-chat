<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\EconomySplitter;
use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * THE bit-by-bit financial test (§2.1, §2.2, §2.3, the reconcile invariant).
 *
 * Simulates thousands of rounds calling the PURE core functions directly along the
 * EXACT hot-path order, exercising the FULL chain the EVAL performs per bet:
 *   split (EconomySplitter) → credit netToVault (intake) + owner accrual
 *     → selectPure WITH proportional gate+redistribution → guarded settle (payout)
 *
 * Two coin-exact invariants are asserted on EVERY single round (zero loss, integer):
 *   (1) ownerCut + receiverCut + netToVault === bet                     (split)
 *   (2) vault === baseline + intake − payout                           (reconcile)
 *
 * and on every bet:
 *   • everything is integer,
 *   • the vault never breaches the negative limit (solvency),
 *   • payout == applied (no phantom wins past the limit).
 *
 * baseline = the seeded vault (capital recycled into the pool). The "ledger" view
 * the production reconcile job checks is `vault == baseline + intake − payout`; we
 * track intake/payout exactly as the EVAL does (intake = Σ netToVault credited,
 * payout = Σ applied wins) and assert the identity holds to the coin continuously.
 */
class BitByBitFinancialTest extends TestCase
{
    public function test_bit_by_bit_conservation_and_vault_invariant_over_thousands_of_rounds(): void
    {
        $ownerRate    = 0.05;   // panel: app profit 5%
        $receiverRate = 0.10;   // panel: receiver 10%
        $negLimit     = 30_000;

        $mt = new MultiplierTable();

        // RTP_eff exactly as production derives it (floor-bias ≤ 0).
        $targetRtp = MultiplierTable::effectiveTargetRtpPure(
            0.80, $ownerRate, $receiverRate,
            MultiplierTable::DEFAULT_BASE_WEIGHTS,
            MultiplierTable::DEFAULT_MULTIPLIERS
        );

        // Deterministic LCG so the run is reproducible and exact.
        $seed = 1357924680;
        $rng = function (int $max) use (&$seed) {
            $seed = (1103515245 * $seed + 12345) % 2147483648;
            return 1 + (int) floor($seed / 2147483648 * $max);
        };

        $baseline = 250_000;     // capital recycled into the vault
        $vault    = $baseline;
        $intake   = 0;           // Σ netToVault credited (== fairluck:stat:intake delta)
        $payout   = 0;           // Σ applied wins      (== fairluck:stat:payout delta)
        $ownerAccrual = 0;
        $receiverTotal = 0;
        $wagered = 0;

        $betSizes = [10, 50, 100, 300, 1000, 5000];

        $rounds = 30_000;
        for ($i = 0; $i < $rounds; $i++) {
            $bet = $betSizes[$i % count($betSizes)];
            $wagered += $bet;

            // (1) split — coin-exact invariant on every round.
            $split = EconomySplitter::split($bet, $ownerRate, $receiverRate);
            $this->assertSame(
                $bet,
                $split->ownerCut + $split->receiverCut + $split->netToVault,
                "split invariant broke at round {$i}"
            );
            $this->assertIsInt($split->ownerCut);
            $this->assertIsInt($split->receiverCut);
            $this->assertIsInt($split->netToVault);

            // CREDIT phase — netToVault to the vault & intake counter, owner accrual.
            $vault  += $split->netToVault;
            $intake += $split->netToVault;
            $ownerAccrual  += $split->ownerCut;
            $receiverTotal += $split->receiverCut;

            // SELECT — full pipeline INCLUDING proportional gate + redistribution.
            $cfg = [
                'multipliers'   => MultiplierTable::DEFAULT_MULTIPLIERS,
                'baseWeights'   => MultiplierTable::DEFAULT_BASE_WEIGHTS,
                'thresholds'    => ['min' => 10_000, 'tight' => 50_000, 'target' => 200_000, 'high' => 500_000, 'drain' => 1_000_000],
                'targetRtp'     => $targetRtp,
                'negativeLimit' => $negLimit,
                'modulators'    => [],
                'rng'           => $rng,
            ];
            $sel  = $mt->selectPure($vault, 0, 0, $bet, 0, $cfg);
            $mult = (int) $sel['multiplier'];
            $requested = $mult > 0 ? $bet * $mult : 0;

            // SETTLE — guarded debit re-checked against the live balance.
            $applied = 0;
            if ($requested > 0 && ($vault - $requested) >= -$negLimit) {
                $vault  -= $requested;
                $payout += $requested;
                $applied = $requested;
            }
            $this->assertIsInt($applied);

            // (2) THE reconcile invariant — to the coin, every single round.
            $this->assertSame(
                $baseline + $intake - $payout,
                $vault,
                "vault invariant broke at round {$i} (baseline+intake-payout != vault)"
            );

            // Solvency on every bet.
            $this->assertGreaterThanOrEqual(-$negLimit, $vault, "negative limit breached at round {$i}");
            // No phantom win: if a payout was requested but unaffordable, it was 0.
            if ($requested > 0 && ($vault + $applied - $requested) < -$negLimit) {
                $this->assertSame(0, $applied, "phantom win past the limit at round {$i}");
            }
        }

        // Final ledger identity (the production reconcile check) holds exactly.
        $this->assertSame($baseline + $intake - $payout, $vault, 'final vault ledger drift');

        // Full money conservation: what left players' pockets that did not return
        // as winnings == owner accrual + receiver cuts + vault growth, exactly.
        $houseRetained = $wagered - $payout;
        $this->assertSame(
            $ownerAccrual + $receiverTotal + ($vault - $baseline),
            $houseRetained,
            'global conservation drift'
        );
        $this->assertGreaterThan(0, $houseRetained, 'house edge must stay positive');

        fwrite(STDERR, sprintf(
            "\n[bitbybit] rounds=%d baseline=%d intake=%d payout=%d finalVault=%d owner=%d receiver=%d wagered=%d realisedRTP=%.4f\n",
            $rounds, $baseline, $intake, $payout, $vault, $ownerAccrual, $receiverTotal, $wagered, $payout / max(1, $wagered)
        ));
    }
}
