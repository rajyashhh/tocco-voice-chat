<?php

namespace Tests\Unit\FairLuck;

use App\Jobs\ProcessLuckyGiftPostJob;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for ProcessLuckyGiftPostJob::expandPackedFairLuckPosts —
 * the batch wire-format expander. New contract: the per-bet RTP deviations
 * (deviation_before / new_deviation) are computed from the shipped start
 * counters with the EXACT recurrence FairLuckServiceV7::processBet uses
 * (was hardcoded 0). Legacy payloads without the counters keep 0.
 */
class PackedPostsExpansionTest extends TestCase
{
    private function expand(array $packed): array
    {
        $job = new ProcessLuckyGiftPostJob(['user_id' => 1]);
        $m = new \ReflectionMethod($job, 'expandPackedFairLuckPosts');
        $m->setAccessible(true);

        return $m->invoke($job, $packed);
    }

    private function common(array $overrides = []): array
    {
        return array_merge([
            'user_id'              => 7,
            'gift_id'              => 3,
            'bet_amount'           => 100,
            'app_fee'              => 1,
            'receiver_fee'         => 10,
            'net_to_vault'         => 89,
            'room_id'              => 55,
            'sender_balance_start' => 10_000,
            'vault_start'          => 50_000,
        ], $overrides);
    }

    public function test_deviation_recurrence_matches_serial_engine_formula(): void
    {
        $targetRtp = 0.99;
        $spentStart = 1_000;
        $receivedStart = 700;

        // bets: [appliedPayout, multiplier]
        $bets = [[0, 0], [500, 5], [0, 0]];

        $posts = $this->expand([
            'common' => $this->common([
                'target_rtp'           => $targetRtp,
                'total_spent_start'    => $spentStart,
                'total_received_start' => $receivedStart,
            ]),
            'bets' => $bets,
        ]);

        $this->assertCount(3, $posts);

        // Replay the EXACT serial recurrence (FairLuckServiceV7::processBet):
        // deviation_before = target - received/spent (before), new_deviation after.
        $spent = $spentStart;
        $received = $receivedStart;
        foreach ($bets as $i => [$applied, $mult]) {
            $expectedBefore = $targetRtp - ($spent > 0 ? $received / $spent : 0);
            $spent += 100;
            $received += $applied;
            $expectedNew = $targetRtp - ($spent > 0 ? $received / $spent : 0);

            $this->assertEqualsWithDelta($expectedBefore, $posts[$i]['transaction']['deviation_before'], 1e-12, "bet $i deviation_before");
            $this->assertEqualsWithDelta($expectedNew, $posts[$i]['stats']['new_deviation'], 1e-12, "bet $i new_deviation");
        }
    }

    public function test_legacy_payload_without_rtp_counters_keeps_zero_deviations(): void
    {
        $posts = $this->expand([
            'common' => $this->common(), // no target_rtp / *_start keys
            'bets'   => [[0, 0], [200, 2]],
        ]);

        foreach ($posts as $i => $post) {
            $this->assertSame(0, $post['transaction']['deviation_before'], "bet $i");
            $this->assertSame(0, $post['stats']['new_deviation'], "bet $i");
        }
    }

    public function test_balance_and_wallet_recurrences_unchanged(): void
    {
        $posts = $this->expand([
            'common' => $this->common([
                'target_rtp'           => 0.99,
                'total_spent_start'    => 0,
                'total_received_start' => 0,
            ]),
            'bets' => [[0, 0], [500, 5]],
        ]);

        // Bet 1: loss. sender 10000 → 9900; wallet 50000+89 → 50089.
        $this->assertSame(10_000, $posts[0]['transaction']['sender_balance_before']);
        $this->assertSame(9_900, $posts[0]['transaction']['sender_balance_after']);
        $this->assertSame(['lucky_wallet' => 50_089], $posts[0]['transaction']['wallets_before']);
        $this->assertSame(['lucky_wallet' => 50_089], $posts[0]['transaction']['wallets_after']);
        $this->assertSame(-100, $posts[0]['stats']['profit_amount']);
        $this->assertFalse($posts[0]['transaction']['is_winner']);

        // Bet 2: win 500. sender 9900 → 10300; wallet 50089+89=50178 → 49678.
        $this->assertSame(9_900, $posts[1]['transaction']['sender_balance_before']);
        $this->assertSame(10_300, $posts[1]['transaction']['sender_balance_after']);
        $this->assertSame(['lucky_wallet' => 50_178], $posts[1]['transaction']['wallets_before']);
        $this->assertSame(['lucky_wallet' => 49_678], $posts[1]['transaction']['wallets_after']);
        $this->assertSame(400, $posts[1]['stats']['profit_amount']);
        $this->assertTrue($posts[1]['transaction']['is_winner']);
        $this->assertSame(5, $posts[1]['transaction']['multiplier']);

        // First bet's deviation_before with zero history is target - 0.
        $this->assertEqualsWithDelta(0.99, $posts[0]['transaction']['deviation_before'], 1e-12);
    }
}
