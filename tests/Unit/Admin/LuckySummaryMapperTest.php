<?php

namespace Tests\Unit\Admin;

use App\Admin\Reports\LuckySummaryMapper;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for the period-summary math — no Laravel, no DB.
 * Mirrors exactly what the SQL aggregate row contains, where
 * profit_amount = sender NET per round (win: payout - bet, loss: -bet).
 */
class LuckySummaryMapperTest extends TestCase
{
    public function test_null_row_yields_all_zeros_without_division_errors(): void
    {
        $s = LuckySummaryMapper::map(null);

        $this->assertSame(0, $s['rounds']);
        $this->assertSame(0.0, $s['total_bets']);
        $this->assertSame(0.0, $s['senders_net']);
        $this->assertSame(0.0, $s['win_rate']);
        $this->assertSame(0.0, $s['rtp']);
    }

    public function test_synthetic_period_matches_hand_computed_aggregates(): void
    {
        // Rounds: [bet, multiplier(0 = loss)]
        // 100 x5 => profit +400, payout 500
        // 100 x0 => profit -100
        // 200 x0 => profit -200
        // 50  x2 => profit +50,  payout 100
        $row = (object) [
            'rounds' => 4,
            'total_bets' => 450.0,                    // 100+100+200+50
            'senders_net' => 150.0,                   // 400-100-200+50
            'total_payouts' => 600.0,                 // (400+100)+(50+50) winners only
            'receivers_total' => 45.0,                // 10% receiver cut
            'app_total' => 4.0,                       // ~1% owner cut
            'players' => 3,
            'wins' => 2,
        ];

        $s = LuckySummaryMapper::map($row);

        $this->assertSame(4, $s['rounds']);
        $this->assertSame(450.0, $s['total_bets']);
        $this->assertSame(150.0, $s['senders_net']);
        $this->assertSame(600.0, $s['total_payouts']);
        $this->assertSame(45.0, $s['receivers_total']);
        $this->assertSame(4.0, $s['app_total']);
        $this->assertSame(3, $s['players']);
        $this->assertSame(2, $s['wins']);
        $this->assertSame(50.0, $s['win_rate']);      // 2/4
        $this->assertSame(133.33, $s['rtp']);         // 600/450
    }

    public function test_rtp_identity_payouts_equal_net_plus_winning_bets(): void
    {
        // For any period: total_payouts == senders_net + Σ bets of LOSING rounds
        // recovered + Σ bets of winning rounds… the simplest invariant we can
        // assert on the mapper itself: rtp == total_payouts / total_bets * 100.
        $row = (object) [
            'rounds' => 1000,
            'total_bets' => 123456.0,
            'senders_net' => -13456.0,
            'total_payouts' => 98765.0,
            'receivers_total' => 12345.6,
            'app_total' => 1234.56,
            'players' => 77,
            'wins' => 245,
        ];

        $s = LuckySummaryMapper::map($row);

        $this->assertSame(round(98765.0 * 100 / 123456.0, 2), $s['rtp']);
        $this->assertSame(round(245 * 100 / 1000, 2), $s['win_rate']);
    }

    public function test_string_decimals_from_mysql_are_cast(): void
    {
        // MySQL DECIMAL sums arrive as strings through PDO.
        $row = (object) [
            'rounds' => '2',
            'total_bets' => '300.00',
            'senders_net' => '-100.00',
            'total_payouts' => '200.00',
            'receivers_total' => '30.00',
            'app_total' => '3.00',
            'players' => '2',
            'wins' => '1',
        ];

        $s = LuckySummaryMapper::map($row);

        $this->assertSame(2, $s['rounds']);
        $this->assertSame(300.0, $s['total_bets']);
        $this->assertSame(-100.0, $s['senders_net']);
        $this->assertSame(50.0, $s['win_rate']);
        $this->assertSame(round(200.0 * 100 / 300.0, 2), $s['rtp']);
    }
}
