<?php

namespace App\Services\FairLuck\V7;

/**
 * EconomySplitter: pure, integer-only split of a gross bet into the three buckets
 * defined by the owner (all rates admin-configurable):
 *
 *   ownerCut    → owner wallet   (guaranteed, taken first)
 *   receiverCut → the receiver   (taken from EVERY bet, regardless of win/loss)
 *   netToVault  → algorithm/luck vault (the recycled pool that pays out wins)
 *
 * Hard invariant: ownerCut + receiverCut + netToVault === gross bet (no coins lost
 * or minted). All arithmetic is integer; rates are converted to basis points and
 * floored, so any sub-coin remainder always stays in the vault — the owner and the
 * receiver are never over-paid, and the house never loses a coin to rounding.
 *
 * This class has ZERO external dependencies (no Redis, no DB, no facades) so it is
 * fully unit-testable in isolation and safe to call on the hot path.
 */
final class EconomySplitter
{
    /**
     * @param int   $betAmount   Gross bet (B), integer coins, > 0.
     * @param float $ownerRate   Owner cut as a fraction (e.g. 0.01 = 1%). Admin-set.
     * @param float $receiverRate Receiver cut as a fraction (e.g. 0.10 = 10%). Admin-set.
     *
     * @return object{ownerCut:int, receiverCut:int, netToVault:int}
     */
    public static function split(int $betAmount, float $ownerRate, float $receiverRate): object
    {
        if ($betAmount <= 0) {
            return (object) ['ownerCut' => 0, 'receiverCut' => 0, 'netToVault' => 0];
        }

        $ownerBps    = self::toBps($ownerRate);
        $receiverBps = self::toBps($receiverRate);

        // Defensive: the two cuts can never exceed the whole bet. If an admin
        // misconfigures rates summing to >= 100%, clamp so netToVault stays >= 0
        // and the invariant holds (owner keeps priority, receiver takes the rest).
        if ($ownerBps + $receiverBps > 10_000) {
            $ownerBps    = min($ownerBps, 10_000);
            $receiverBps = 10_000 - $ownerBps;
        }

        $ownerCut    = intdiv($betAmount * $ownerBps, 10_000);
        $receiverCut = intdiv($betAmount * $receiverBps, 10_000);
        $netToVault  = $betAmount - $ownerCut - $receiverCut;

        return (object) [
            'ownerCut'    => $ownerCut,
            'receiverCut' => $receiverCut,
            'netToVault'  => $netToVault,
        ];
    }

    /**
     * Cumulative-floor cut for ONE unit in a stream of equal-price units, so the
     * stream as a whole loses no sub-coin to flooring.
     *
     * Per-unit flooring is lossy: floor(50 * 1%) = floor(0.5) = 0, so a 1% owner
     * fee on 50-coin bets collects NOTHING. Instead we track the running total
     * floor(k*price*rate) and hand each unit the increment since the previous one.
     * Over n units the cuts sum to floor(n*price*rate) EXACTLY — the same result
     * as taxing the whole combo at once, but expressed per unit so the transaction
     * log and the per-bet EVAL stay accurate.
     *
     * @param int   $priorUnits How many equal-price units already taxed in this
     *                          stream (0 for the first). The "stream" is the owner
     *                          across the whole combo, or one receiver's own bets.
     * @param int   $unitPrice  Gross price of a single unit (> 0).
     * @param float $rate       Cut as a fraction (0.01 = 1%). Admin-set.
     * @return int  The integer cut owed by THIS unit (>= 0).
     */
    public static function incrementalCut(int $priorUnits, int $unitPrice, float $rate): int
    {
        if ($unitPrice <= 0 || $priorUnits < 0) {
            return 0;
        }
        $bps = self::toBps($rate);
        if ($bps === 0) {
            return 0;
        }

        $after  = intdiv(($priorUnits + 1) * $unitPrice * $bps, 10_000);
        $before = intdiv($priorUnits * $unitPrice * $bps, 10_000);

        return $after - $before;
    }

    /**
     * Public exposer of the basis-point conversion, used by the money path to pass
     * the owner rate into the batchSettle EVAL for exact bps-coin fee accrual.
     */
    public static function ownerBpsFor(float $rate): int
    {
        return self::toBps($rate);
    }

    /**
     * Convert a fractional rate to integer basis points, clamped to [0, 10000].
     * round() here only maps the admin's decimal (0.015 → 150 bps) to an integer
     * basis-point grid; all downstream money math stays integer.
     */
    private static function toBps(float $rate): int
    {
        if ($rate <= 0) {
            return 0;
        }
        $bps = (int) round($rate * 10_000);

        return max(0, min(10_000, $bps));
    }
}
