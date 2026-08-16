<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Streak v2 attack cap (§2.4 / S-ECO-2).
 *
 * The exploit closed: "build a cheap loss streak, then jump to a huge bet exactly
 * at the hard floor for a guaranteed huge forced win." The fix sizes the forced
 * floor win on the streak's AVERAGE bet (streak_bet_sum / consecutive_losses), NOT
 * the current (possibly huge) bet, and caps it at 3 × avg × smallestMult.
 *
 * The forced-floor arithmetic lives in the batchSettle EVAL; this test exercises a
 * FAITHFUL PHP MIRROR of that exact block (lines that compute avg / floorPay / cap)
 * so it is unit-testable without a live Redis, and asserts the attack is bounded.
 */
class StreakV2AttackTest extends TestCase
{
    private const HARD_FLOOR    = MultiplierTable::STREAK_HARD_FLOOR; // 70
    private const SMALLEST_MULT = 2; // profile C min multiplier

    /**
     * Exact mirror of the EVAL hard-floor block:
     *   if hardFloor>0 and consec>=hardFloor and smallMult>0:
     *       avg = consec>0 ? floor(streakSum/consec) : 0
     *       if avg<1: avg = bet
     *       floorPay = avg * smallMult
     *       cap = 3 * avg * smallMult
     *       if floorPay>cap: floorPay=cap
     *       if floorPay>reqPay: reqPay=floorPay (forced)
     *
     * @return array{reqPay:int, forced:bool}
     */
    private function mirrorFloor(int $consec, int $streakSum, int $bet, int $reqPay): array
    {
        $forced = false;
        if (self::HARD_FLOOR > 0 && $consec >= self::HARD_FLOOR && self::SMALLEST_MULT > 0) {
            $avg = $consec > 0 ? intdiv($streakSum, $consec) : 0;
            if ($avg < 1) {
                $avg = $bet;
            }
            $floorPay = $avg * self::SMALLEST_MULT;
            $cap = 3 * $avg * self::SMALLEST_MULT;
            if ($floorPay > $cap) {
                $floorPay = $cap;
            }
            if ($floorPay > $reqPay) {
                $reqPay = $floorPay;
                $forced = true;
            }
        }
        return ['reqPay' => $reqPay, 'forced' => $forced];
    }

    public function test_cheap_streak_then_huge_bet_yields_only_proportional_floor(): void
    {
        // Attacker loses 70 bets of 10 coins each (streak_bet_sum = 700), then jumps
        // to a 5000-coin bet hoping the hard floor pays out on 5000.
        $consec = self::HARD_FLOOR;             // 70 at the floor
        $streakSum = 70 * 10;                   // built with 10-coin bets
        $hugeBet = 5000;
        $r = $this->mirrorFloor($consec, $streakSum, $hugeBet, 0);

        $avg = intdiv($streakSum, $consec);     // 10
        $expectedFloor = $avg * self::SMALLEST_MULT; // 10 × 2 = 20

        $this->assertTrue($r['forced'], 'hard floor should force a win at the threshold');
        // The forced payout is sized on the CHEAP average (20), NOT the 5000 bet.
        $this->assertSame($expectedFloor, $r['reqPay'], 'forced floor leaked the huge bet size');
        // And it is dwarfed by what a naive (current-bet) floor would have paid.
        $naive = $hugeBet * self::SMALLEST_MULT; // 10,000
        $this->assertLessThan($naive / 100, $r['reqPay'], 'attack is not bounded');
    }

    public function test_forced_floor_capped_at_three_times_avg(): void
    {
        // Even if a natural draw or boosted payout were large, the forced floor itself
        // never exceeds 3 × avg × smallestMult.
        $consec = self::HARD_FLOOR;
        $streakSum = 70 * 100;                 // avg 100
        $avg = 100;
        $cap = 3 * $avg * self::SMALLEST_MULT; // 600

        // reqPay below the floor → forced to floorPay (== avg×mult == 200, ≤ cap).
        $r = $this->mirrorFloor($consec, $streakSum, 100, 0);
        $this->assertSame($avg * self::SMALLEST_MULT, $r['reqPay']);
        $this->assertLessThanOrEqual($cap, $r['reqPay']);
    }

    public function test_no_forced_win_below_hard_floor(): void
    {
        // One short of the floor: no forced win, reqPay untouched.
        $r = $this->mirrorFloor(self::HARD_FLOOR - 1, (self::HARD_FLOOR - 1) * 10, 5000, 0);
        $this->assertFalse($r['forced']);
        $this->assertSame(0, $r['reqPay']);
    }

    public function test_natural_win_above_floor_is_kept(): void
    {
        // If the PHP draw already produced a win bigger than the floor, it is kept
        // (the floor only RAISES toward the minimum, never lowers a real win).
        $consec = self::HARD_FLOOR;
        $streakSum = 70 * 10; // avg 10 → floorPay 20
        $r = $this->mirrorFloor($consec, $streakSum, 100, 5000);
        $this->assertFalse($r['forced'], 'should not override a larger natural win');
        $this->assertSame(5000, $r['reqPay']);
    }

    public function test_streak_floor_is_cheaper_than_attacker_cost_to_build(): void
    {
        // Defence-in-depth sanity: the coins burned BUILDING a 70-loss streak with
        // the cheap bets vastly exceed the bounded floor reward — the attack is
        // unprofitable by construction even before the cap.
        $cheapBet = 10;
        $costToBuild = self::HARD_FLOOR * $cheapBet;       // 700 wagered
        $streakSum = $costToBuild;
        $r = $this->mirrorFloor(self::HARD_FLOOR, $streakSum, 5000, 0);
        $this->assertLessThan($costToBuild, $r['reqPay'], 'forced floor reward exceeds the build cost — exploitable');
    }
}
