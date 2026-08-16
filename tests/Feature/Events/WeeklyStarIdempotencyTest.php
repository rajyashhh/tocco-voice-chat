<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Weekly Star — financial idempotency & atomicity (plan items 2.9 / 2.14 +
 * unique constraint + null-sender guard).
 *
 * Code under test:
 *   Modules/Events/Console/WeeklyStarWinner.php
 *   Modules/Events/Database/Migrations/2026_08_12_000000_add_unique_weekly_star_user_to_winners.php
 *
 * Acceptance criteria:
 *  (2.9)  Re-running `weekly-star-winner` for the same event pays each of the
 *         top-3 senders exactly once (one winners row per (weekly_star_id,
 *         user_id), one WEEKLY_STAR coin log, di incremented once).
 *  (2.14) A crash after the di increment rolls back the winner row, the coin
 *         log, the winner_rewards row and the balance; rerun recovers cleanly.
 *  (2.x)  UNIQUE(weekly_star_id,user_id) enforced at DB layer.
 *  (2.x)  A deleted sender (dangling sender_id in gift_logs) is skipped without
 *         aborting the whole run; remaining winners still get paid.
 *  (2.x)  winner_rewards.type is filled with 'weekly_star'.
 */
class WeeklyStarIdempotencyTest extends EventsQaTestCase
{
    /**
     * Weekly star ending today (endToday scope matches DATE(CONVERT_TZ(end_date))
     * = today with timezone '+00:00'), with one tracked gift and coins rewards
     * for levels 1..3.
     */
    private function seedEndingEvent(array $rewardLevels = [1 => 300, 2 => 200, 3 => 100]): int
    {
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::now()->subWeek()->toDateString();

        $weeklyStarId = DB::table('weekly_stars')->insertGetId([
            'start_date' => $startDate, 'end_date' => $endDate, 'type' => 'weekly_star',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $giftId = $this->insertGift(50);
        DB::table('weekly_star_gifts')->insert([
            'weekly_star_id' => $weeklyStarId, 'gift_id' => $giftId,
        ]);

        foreach ($rewardLevels as $level => $coins) {
            DB::table('rewards')->insert([
                'weekly_star_id' => $weeklyStarId, 'type' => 'coins', 'level' => $level,
                'target' => (string) $coins, 'expire' => 7,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $giftId;
    }

    private function winnerRewardsCountFor(int $userId): int
    {
        return DB::table('winner_rewards')
            ->join('winners', 'winners.id', '=', 'winner_rewards.winner_id')
            ->where('winners.user_id', $userId)
            ->count();
    }

    private function sendTrackedGift(int $giftId, int $senderId, float $price): void
    {
        $this->insertGiftLog([
            'giftId' => $giftId, 'sender_id' => $senderId, 'receiver_id' => 1,
            'giftPrice' => $price,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);
    }

    public function test_2_9_rerun_does_not_duplicate_payout(): void
    {
        $giftId = $this->seedEndingEvent();

        $u1 = $this->makeUser(['di' => 0]);
        $u2 = $this->makeUser(['di' => 0]);
        $u3 = $this->makeUser(['di' => 0]);

        $this->sendTrackedGift($giftId, $u1->id, 5000);
        $this->sendTrackedGift($giftId, $u2->id, 3000);
        $this->sendTrackedGift($giftId, $u3->id, 1000);

        Artisan::call('weekly-star-winner');
        Artisan::call('weekly-star-winner');
        Artisan::call('weekly-star-winner');

        foreach ([[$u1, 300, 1], [$u2, 200, 2], [$u3, 100, 3]] as [$u, $prize, $level]) {
            $winnerRows = DB::table('winners')->where('user_id', $u->id)->get();
            $this->assertCount(1, $winnerRows, "user {$u->id}: one winners row after 3 runs");
            $this->assertSame($level, (int) $winnerRows[0]->level);
            $this->assertSame(1, $this->coinLogCount($u->id, 'weekly_star'), "user {$u->id}: one coin log");
            $this->assertSame((float) $prize, $this->di($u->id), "user {$u->id}: paid exactly once");
        }
    }

    public function test_2_14_partial_failure_rolls_back_whole_winner_unit(): void
    {
        $giftId = $this->seedEndingEvent([1 => 300]);
        $u1 = $this->makeUser(['di' => 0]);
        $this->sendTrackedGift($giftId, $u1->id, 5000);

        $this->armDiIncrementSabotage();
        try {
            Artisan::call('weekly-star-winner');
            $this->fail('sabotage exception should have propagated');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('QA sabotage', $e->getMessage());
        } finally {
            $this->disarmSabotage();
        }

        $this->assertSame(0, DB::table('winners')->where('user_id', $u1->id)->count(), 'winners row must roll back');
        $this->assertSame(0, $this->winnerRewardsCountFor($u1->id), 'winner_rewards must roll back');
        $this->assertSame(0, $this->coinLogCount($u1->id, 'weekly_star'), 'coin log must roll back');
        $this->assertSame(0.0, $this->di($u1->id), 'di must roll back');

        Artisan::call('weekly-star-winner');
        $this->assertSame(1, DB::table('winners')->where('user_id', $u1->id)->count());
        $this->assertSame((float) 300, $this->di($u1->id));
    }

    public function test_unique_weekly_star_user_enforced_at_db_layer(): void
    {
        $this->seedEndingEvent();
        $weeklyStarId = (int) DB::table('weekly_stars')->max('id');

        DB::table('winners')->insert([
            'weekly_star_id' => $weeklyStarId, 'user_id' => 42, 'level' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('winners')->insert([
            'weekly_star_id' => $weeklyStarId, 'user_id' => 42, 'level' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_deleted_sender_is_skipped_and_next_winners_still_paid(): void
    {
        $giftId = $this->seedEndingEvent();

        $u2 = $this->makeUser(['di' => 0]);
        $u3 = $this->makeUser(['di' => 0]);

        // Rank 1 belongs to a sender id that no longer exists in users.
        $ghostId = (int) DB::table('users')->max('id') + 999999;
        $this->sendTrackedGift($giftId, $ghostId, 9000);
        $this->sendTrackedGift($giftId, $u2->id, 3000);
        $this->sendTrackedGift($giftId, $u3->id, 1000);

        Artisan::call('weekly-star-winner');

        $this->assertSame(0, DB::table('winners')->where('user_id', $ghostId)->count(), 'ghost sender must not be crowned');
        // Ranks are positional (index+1): the surviving senders take levels 2 and 3.
        $this->assertSame(1, DB::table('winners')->where('user_id', $u2->id)->count(), 'existing sender must still be paid');
        $this->assertSame(1, DB::table('winners')->where('user_id', $u3->id)->count());
        $this->assertSame((float) 200, $this->di($u2->id));
        $this->assertSame((float) 100, $this->di($u3->id));
    }

    public function test_winner_rewards_row_carries_type_and_future_expiry(): void
    {
        $giftId = $this->seedEndingEvent([1 => 300]);
        $u1 = $this->makeUser(['di' => 0]);
        $this->sendTrackedGift($giftId, $u1->id, 5000);

        Artisan::call('weekly-star-winner');

        // winner_rewards.winner_id references winners.id (the crowning row),
        // so resolve through the winners table.
        $winnerRowId = DB::table('winners')->where('user_id', $u1->id)->value('id');
        $this->assertNotNull($winnerRowId, 'winners row must exist');
        $rr = DB::table('winner_rewards')->where('winner_id', $winnerRowId)->first();
        $this->assertNotNull($rr, 'winner_rewards row must exist');
        $this->assertSame('weekly_star', $rr->type, 'winner_rewards.type must be filled');
        // expire=7 days => expaired_at ~ now+7d (addDays semantics, not Carbon::parse("7")).
        $this->assertEqualsWithDelta(
            Carbon::now()->addDays(7)->timestamp,
            Carbon::parse($rr->expaired_at)->timestamp,
            120,
            'expaired_at must be now()+expire days'
        );
    }
}