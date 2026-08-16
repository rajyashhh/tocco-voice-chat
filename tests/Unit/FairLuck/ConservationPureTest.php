<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\EconomySplitter;
use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Conservation of money over the FULL per-hit pipeline (pure replica of the
 * hot path: guarded debit → split → credit → selectPure → guarded settle →
 * win credit → per-receiver cut map), mixed multi-receiver combos:
 *
 *   Σdeductions == Σpayouts + ownerAccrual + Σreceiver_cuts + Δvault   (drift 0)
 *   per receiver: credited == Σ floor(B × r_recv) over their OWN hits only
 */
class ConservationPureTest extends TestCase
{
    public function test_zero_drift_and_exact_per_receiver_attribution_over_mixed_combos(): void
    {
        $ownerRate = 0.01;
        $receiverRate = 0.10;
        $negLimit = 30_000;

        $mt = new MultiplierTable();
        $seed = 246813579;
        $rng = function (int $max) use (&$seed) {
            $seed = (1103515245 * $seed + 12345) % 2147483648;
            return 1 + (int) floor($seed / 2147483648 * $max);
        };
        $cfg = [
            'multipliers' => MultiplierTable::DEFAULT_MULTIPLIERS,
            'baseWeights' => MultiplierTable::DEFAULT_BASE_WEIGHTS,
            'thresholds' => ['min' => 10_000, 'tight' => 50_000, 'target' => 200_000, 'high' => 500_000, 'drain' => 1_000_000],
            'targetRtp' => MultiplierTable::effectiveTargetRtpPure(
                0.89, $ownerRate, $receiverRate,
                MultiplierTable::DEFAULT_BASE_WEIGHTS, MultiplierTable::DEFAULT_MULTIPLIERS
            ),
            'negativeLimit' => $negLimit,
            'rng' => $rng,
        ];

        $vaultSeed = 200_000;
        $vault = $vaultSeed;
        $owner = 0;
        $receiverCredits = [];   // applied via the per-amount group map
        $expectedPerReceiver = []; // Σ floor(B × r_recv) over OWN hits
        $deducted = 0;
        $paidOut = 0;
        $senderDi = 50_000_000;
        $spent = 0; $received = 0; $streak = 0;

        // 5,000 hits across mixed shapes: N ∈ {1,2,5,10}, counts ∈ {1,2,9}.
        $shapes = [[1, 9], [2, 2], [5, 1], [10, 1], [1, 1], [2, 9]];
        $prices = [100, 300, 1000];
        $hits = 0; $s = 0;
        while ($hits < 5_000) {
            [$n, $c] = $shapes[$s % count($shapes)];
            $price = $prices[$s % count($prices)];
            $num = 1 + ($s % 3);
            $unit = $price * $num; // B = P × m
            $s++;

            $receivers = range(1000, 1000 + $n - 1);
            $cutMap = [];
            for ($round = 0; $round < $c; $round++) {
                foreach ($receivers as $rid) {
                    // guarded debit
                    if ($senderDi < $unit) { break 2; }
                    $senderDi -= $unit;
                    $deducted += $unit;
                    $hits++;

                    $split = EconomySplitter::split($unit, $ownerRate, $receiverRate);
                    $this->assertSame($unit, $split->ownerCut + $split->receiverCut + $split->netToVault);

                    $owner += $split->ownerCut;
                    $vault += $split->netToVault;
                    $cutMap[$rid] = ($cutMap[$rid] ?? 0) + $split->receiverCut;
                    $expectedPerReceiver[$rid] = ($expectedPerReceiver[$rid] ?? 0)
                        + intdiv($unit * (int) round($receiverRate * 10_000), 10_000);

                    $sel = $mt->selectPure($vault, $spent, $received, $unit, $streak, $cfg);
                    $reqPay = ((int) $sel['multiplier']) > 0 ? $unit * (int) $sel['multiplier'] : 0;
                    $applied = 0;
                    if ($reqPay > 0 && ($vault - $reqPay) >= -$negLimit) {
                        $vault -= $reqPay;
                        $applied = $reqPay;
                    }
                    if ($applied > 0) {
                        $senderDi += $applied;
                        $paidOut += $applied;
                        $received += $applied;
                        $streak = 0;
                    } else {
                        $streak++;
                    }
                    $spent += $unit;

                    $this->assertGreaterThanOrEqual(-$negLimit, $vault);
                }
            }

            // Apply the per-receiver cut map exactly as creditReceiversFromMap does.
            foreach ($cutMap as $rid => $amount) {
                $receiverCredits[$rid] = ($receiverCredits[$rid] ?? 0) + $amount;
            }
        }

        $receiverTotal = array_sum($receiverCredits);

        // THE conservation identity — to the coin, drift exactly 0.
        $this->assertSame(
            $deducted,
            $paidOut + $owner + $receiverTotal + ($vault - $vaultSeed),
            'money drift detected'
        );

        // Per-receiver: credited == Σ own floor(B × r_recv), never the aggregate.
        foreach ($expectedPerReceiver as $rid => $expected) {
            $this->assertSame($expected, $receiverCredits[$rid], "receiver {$rid} attribution wrong");
        }

        $this->assertGreaterThan(4_000, $hits);
    }
}
