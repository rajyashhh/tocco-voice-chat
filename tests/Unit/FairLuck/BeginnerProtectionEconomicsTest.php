<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\BeginnerProtection;
use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * BeginnerProtection ECONOMICS (§4.1–4.3, closes B-ECO-1/2/B-ACC-2).
 *
 *  1. Eligibility = BOTH conditions (real paid charge AND age window) — already in
 *     BeginnerProtectionTest; re-asserted here at the boundaries.
 *  2. The max-bet ATTACKER (a fake account that only ever max-bets while protected)
 *     must face a clearly NEGATIVE expected value — the boosted table is calibrated
 *     on RTP_boost (0.92) and has NO tail (≤ ×100), so there is no subsidised
 *     jackpot to arbitrage. Measured empirically on the boosted shape.
 *  3. Budget CONSUMPTION: the EVAL consumes
 *        consumed_i = min(bp_remaining, max(0, applied_i − floor(bet × RTP_boost_bps/10000)))
 *     i.e. only the OVERRUN above the boosted table's OWN expected return (B-ACC-2),
 *     and stops exactly at 0 (bp_state→2, rest of combo normal). A faithful PHP
 *     mirror of that EVAL arithmetic is exercised here.
 */
class BeginnerProtectionEconomicsTest extends TestCase
{
    private const RTP_BOOST_BPS = 9200; // 0.92

    private function rng(int $seed): callable
    {
        return function (int $max) use (&$seed) {
            $seed = (1103515245 * $seed + 12345) % 2147483648;
            return 1 + (int) floor($seed / 2147483648 * $max);
        };
    }

    private function boostedCfg(callable $rng): array
    {
        $shape = BeginnerProtection::boostedShape(
            MultiplierTable::DEFAULT_MULTIPLIERS,
            MultiplierTable::DEFAULT_BASE_WEIGHTS
        );
        return [
            'multipliers'   => $shape['multipliers'],
            'baseWeights'   => $shape['baseWeights'],
            'thresholds'    => ['min' => 10_000, 'tight' => 50_000, 'target' => 200_000, 'high' => 500_000, 'drain' => 1_000_000],
            'targetRtp'     => self::RTP_BOOST_BPS / 10_000,
            'negativeLimit' => 30_000,
            'modulators'    => [],
            'rng'           => $rng,
        ];
    }

    // ── (1) eligibility boundaries ──────────────────────────────────────────

    public function test_eligibility_requires_both_conditions(): void
    {
        $this->assertTrue(BeginnerProtection::isEligible(true, 0, 10));   // youngest + charged
        $this->assertTrue(BeginnerProtection::isEligible(true, 9, 10));   // last eligible day
        $this->assertFalse(BeginnerProtection::isEligible(true, 10, 10)); // age == window → out
        $this->assertFalse(BeginnerProtection::isEligible(false, 0, 10)); // never charged
        $this->assertFalse(BeginnerProtection::isEligible(true, 5, 0));   // window disabled
    }

    // ── (2) max-bet attacker faces negative EV ──────────────────────────────

    public function test_max_bet_attacker_during_protection_is_expected_to_lose(): void
    {
        $mt = new MultiplierTable();
        $rng = $this->rng(909_090);
        $cfg = $this->boostedCfg($rng);

        // Attacker max-bets on a huge vault (so the gate never helps them) and
        // collects every boosted win. With RTP_boost 0.92 and no tail, EV < bet.
        $vault = 2_000_000_000;
        $bet = 5000;
        $n = 1_000_000;
        $paid = 0;
        for ($i = 0; $i < $n; $i++) {
            $sel = $mt->selectPure($vault, 0, 0, $bet, 0, $cfg);
            $m = (int) $sel['multiplier'];
            if ($m > 0) {
                $paid += $bet * $m;
            }
        }
        $rtp = $paid / ($n * $bet);

        // Clearly negative EV (target 0.92 → ~ −8%), and certainly never ≥ 1.0.
        $this->assertLessThan(1.0, $rtp, "attacker EV is not negative (RTP {$rtp})");
        $this->assertLessThanOrEqual(self::RTP_BOOST_BPS / 10_000 + 0.02, $rtp, "boosted RTP {$rtp} above the cap");

        fwrite(STDERR, sprintf("\n[bp-attacker] boostedRTP=%.4f edge=%.2f%%\n", $rtp, (1 - $rtp) * 100));
    }

    public function test_boosted_table_has_no_tail(): void
    {
        $shape = BeginnerProtection::boostedShape(
            MultiplierTable::DEFAULT_MULTIPLIERS,
            MultiplierTable::DEFAULT_BASE_WEIGHTS
        );
        foreach ($shape['multipliers'] as $m) {
            $this->assertLessThanOrEqual(BeginnerProtection::TAIL_CUTOFF_MULT, $m, "tail tier {$m} leaked into the boost");
        }
        $this->assertNotContains(500, $shape['multipliers']);
        $this->assertNotContains(2000, $shape['multipliers']);
    }

    // ── (3) budget consumption mirrors the EVAL exactly ─────────────────────

    /**
     * Faithful PHP mirror of the batchSettle EVAL bp-consumption block (§4.3):
     *   useBoost = (bpState==1 && bpRemain>0)
     *   reqPay   = useBoost ? boostedPayout : normalPayout
     *   appPay   = (vault - reqPay >= -limit) ? reqPay : 0
     *   over     = max(0, appPay - floor(bet × rtpBoostBps/10000))
     *   take     = min(bpRemain, over)
     *   bpRemain -= take; if bpRemain<=0 → bpState=2
     */
    private function mirrorConsume(int $bet, int $appPay, bool $useBoost, int &$bpRemain, int &$bpState): int
    {
        if (!$useBoost || $appPay <= 0) {
            return 0;
        }
        $base = intdiv($bet * self::RTP_BOOST_BPS, 10_000);
        $over = max(0, $appPay - $base);
        $take = min($bpRemain, $over);
        if ($take > 0) {
            $bpRemain -= $take;
            // bpConsumed accrues $take
        }
        if ($bpRemain <= 0) {
            $bpRemain = 0;
            $bpState = 2;
        }
        return $take;
    }

    public function test_budget_consumes_only_overrun_and_stops_at_zero(): void
    {
        $budget = 50_000;
        $bpRemain = $budget;
        $bpState = 1;
        $bet = 100;
        $boostBase = intdiv($bet * self::RTP_BOOST_BPS, 10_000); // 92

        $consumedTotal = 0;
        $hits = 0;

        // A ×2 boosted win pays 200; overrun above the 92 baseline = 108 per win.
        // Losses (appPay 0) consume nothing. Drive boosted wins until budget gone.
        $seed = 31_337;
        $rng = function (int $max) use (&$seed) {
            $seed = (1103515245 * $seed + 12345) % 2147483648;
            return 1 + (int) floor($seed / 2147483648 * $max);
        };
        $mt = new MultiplierTable();
        $cfg = $this->boostedCfg($rng);
        $vault = 2_000_000_000;

        for ($i = 0; $i < 100_000 && $bpState === 1; $i++) {
            $sel = $mt->selectPure($vault, 0, 0, $bet, 0, $cfg);
            $m = (int) $sel['multiplier'];
            $reqPay = $m > 0 ? $bet * $m : 0;
            $appPay = $reqPay; // huge vault → always affordable
            $useBoost = ($bpState === 1 && $bpRemain > 0);
            $consumedTotal += $this->mirrorConsume($bet, $appPay, $useBoost, $bpRemain, $bpState);
            $hits++;
        }

        // Budget is fully consumed (never over-spent) and the state flips to 2.
        $this->assertSame(2, $bpState, 'budget did not exhaust to bp_state=2');
        $this->assertLessThanOrEqual($budget, $consumedTotal, 'consumed more than the budget');
        // Within one win's overrun of the full budget (the last take is clamped).
        $this->assertGreaterThanOrEqual($budget - (200 - $boostBase), $consumedTotal, 'budget under-consumed');
        $this->assertSame(0, $bpRemain, 'bp_remaining not zeroed at exhaustion');

        fwrite(STDERR, sprintf(
            "\n[bp-consume] budget=%d consumed=%d hits=%d boostBase=%d\n",
            $budget, $consumedTotal, $hits, $boostBase
        ));
    }

    public function test_loss_consumes_no_budget(): void
    {
        $bpRemain = 10_000;
        $bpState = 1;
        // A losing hit (appPay 0) must never touch the budget.
        $take = $this->mirrorConsume(100, 0, true, $bpRemain, $bpState);
        $this->assertSame(0, $take);
        $this->assertSame(10_000, $bpRemain);
        $this->assertSame(1, $bpState);
    }

    public function test_normal_hit_consumes_no_budget(): void
    {
        $bpRemain = 10_000;
        $bpState = 1;
        // A hit that did NOT use boost (useBoost=false) consumes nothing.
        $take = $this->mirrorConsume(100, 200, false, $bpRemain, $bpState);
        $this->assertSame(0, $take);
        $this->assertSame(10_000, $bpRemain);
    }
}
