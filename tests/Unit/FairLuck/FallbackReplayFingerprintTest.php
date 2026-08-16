<?php

namespace Tests\Unit\FairLuck;

use App\Console\Commands\ReplayLuckyFallbackCredits;
use PHPUnit\Framework\TestCase;

/**
 * §7.1 — pure contract test for the fallback-replay fingerprint + the expected
 * one-time backlog. No DB/Redis: only the content-addressing and the baseline
 * sanity-check constants the dry-run compares against.
 *
 * The fingerprint MUST be:
 *   - deterministic (same bytes → same id), so INSERT IGNORE collapses re-runs
 *     and duplicate list entries onto one journal row (no double credit);
 *   - byte-sensitive (any change → different id), so a genuinely different
 *     compensation is never folded into another's row.
 */
class FallbackReplayFingerprintTest extends TestCase
{
    public function test_fingerprint_is_deterministic_for_identical_bytes(): void
    {
        $raw = json_encode([
            'type' => 'bet_refund', 'user_id' => 3818, 'amount' => 100,
            'gift_id' => 7, 'room_id' => 42, 'reason' => 'x', 'ts' => 1718200000,
        ]);

        $this->assertSame(
            ReplayLuckyFallbackCredits::fingerprint($raw),
            ReplayLuckyFallbackCredits::fingerprint($raw),
            'Same raw JSON must always produce the same fingerprint.'
        );
        $this->assertSame(sha1($raw), ReplayLuckyFallbackCredits::fingerprint($raw));
        $this->assertSame(40, strlen(ReplayLuckyFallbackCredits::fingerprint($raw)));
    }

    public function test_fingerprint_differs_when_any_field_changes(): void
    {
        $base = ['type' => 'bet_refund', 'user_id' => 3818, 'amount' => 100, 'ts' => 1];

        $a = ReplayLuckyFallbackCredits::fingerprint(json_encode($base));
        $b = ReplayLuckyFallbackCredits::fingerprint(json_encode(['amount' => 101] + $base));
        $c = ReplayLuckyFallbackCredits::fingerprint(json_encode(['user_id' => 9999] + $base));

        $this->assertNotSame($a, $b, 'A different amount must change the fingerprint.');
        $this->assertNotSame($a, $c, 'A different user must change the fingerprint.');
    }

    public function test_expected_backlog_baseline_matches_design(): void
    {
        // The design pins the known one-time backlog: 8 items / Σ=550 / user 3818.
        $this->assertSame(8, ReplayLuckyFallbackCredits::EXPECTED_COUNT);
        $this->assertSame(550, ReplayLuckyFallbackCredits::EXPECTED_SUM);
        $this->assertSame(3818, ReplayLuckyFallbackCredits::EXPECTED_USER);
    }

    public function test_a_550_sum_over_8_items_for_user_3818_satisfies_the_baseline(): void
    {
        // Models the dry-run aggregation: 8 fallback items for user 3818 summing
        // to 550 must satisfy every baseline assertion the command makes.
        $items = [50, 100, 75, 25, 100, 50, 100, 50]; // 8 items, Σ=550
        $count = count($items);
        $sum = array_sum($items);
        $byUser = [3818 => $sum];

        $this->assertSame(ReplayLuckyFallbackCredits::EXPECTED_COUNT, $count);
        $this->assertSame(ReplayLuckyFallbackCredits::EXPECTED_SUM, $sum);
        $this->assertSame(
            ReplayLuckyFallbackCredits::EXPECTED_SUM,
            $byUser[ReplayLuckyFallbackCredits::EXPECTED_USER] ?? 0
        );
    }
}
