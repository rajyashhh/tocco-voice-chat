<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * PK Event — financial idempotency, atomicity, and the new filters
 * (plan items 3.6 + unique constraint + pk_type reward filter + roomowner_id=0).
 *
 * Code under test:
 *   Modules/Events/Console/PKEventWinnerCommand.php
 *   Modules/Events/Database/Migrations/2026_08_12_000001_add_unique_index_to_pk_winners_table.php
 *
 * Acceptance criteria:
 *  (3.6) Re-running `pk-event-winner` pays each winner exactly once per
 *        (pk_event_id, user_id, pk_type): one pk_winners row, one PK coin log
 *        per coins reward, di incremented once.
 *  (3.x) UNIQUE(pk_event_id, user_id, pk_type) enforced at the DB layer.
 *  (3.x) Rewards are filtered by pk_type: the pk-king winner receives ONLY the
 *        pk-king reward for their level, not the pk-star / pk-room rewards of
 *        the same level (previous double-accounting defect).
 *  (3.x) roomowner_id = 0 rows are excluded from the pk-room ranking.
 *  (3.x) Crash after di increment rolls back winner + rewards + coin log; the
 *        rerun recovers and pays once.
 *  (3.x) Same user winning in two categories gets one row per category (the
 *        unique key includes pk_type).
 */
class PkEventIdempotencyTest extends EventsQaTestCase
{
    /** PK event ending today, with coins rewards per (level, pk_type). */
    private function seedEndingPkEvent(array $rewards): int
    {
        $pkEventId = DB::table('pk_events')->insertGetId([
            'start_date' => Carbon::now()->subWeek()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($rewards as [$level, $pkType, $coins]) {
            DB::table('pk_rewards')->insert([
                'pk_event_id' => $pkEventId, 'type' => 'coins', 'level' => $level,
                'target' => (string) $coins, 'pk_type' => $pkType, 'expire' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $pkEventId;
    }

    private function sendPkGift(int $senderId, int $receiverId, int $roomOwnerId, float $price): void
    {
        $this->insertGiftLog([
            'giftId' => 1, 'sender_id' => $senderId, 'receiver_id' => $receiverId,
            'roomowner_id' => $roomOwnerId, 'giftPrice' => $price, 'pk' => 1,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);
    }

    public function test_3_6_rerun_does_not_duplicate_payout_across_categories(): void
    {
        $sender = $this->makeUser(['di' => 0]);
        $receiver = $this->makeUser(['di' => 0]);
        $owner = $this->makeUser(['di' => 0]);

        $this->seedEndingPkEvent([
            [1, 'pk-king', 500],
            [1, 'pk-star', 300],
            [1, 'pk-room', 200],
        ]);

        $this->sendPkGift($sender->id, $receiver->id, $owner->id, 4000);

        Artisan::call('pk-event-winner');
        Artisan::call('pk-event-winner');
        Artisan::call('pk-event-winner');

        foreach ([
            [$sender, 'pk-king', 500],
            [$receiver, 'pk-star', 300],
            [$owner, 'pk-room', 200],
        ] as [$u, $pkType, $prize]) {
            $rows = DB::table('pk_winners')->where('user_id', $u->id)->where('pk_type', $pkType)->get();
            $this->assertCount(1, $rows, "user {$u->id} ({$pkType}): one winner row after 3 runs");
            $this->assertSame(1, $this->coinLogCount($u->id, 'pk_event'), "user {$u->id}: one PK coin log");
            $this->assertSame((float) $prize, $this->di($u->id), "user {$u->id}: paid exactly once");
        }
    }

    public function test_pk_type_filter_winner_gets_only_own_category_rewards(): void
    {
        $sender = $this->makeUser(['di' => 0]);
        $receiver = $this->makeUser(['di' => 0]);

        // Same level (1) carries three different-category rewards. Before the
        // fix the level filter alone attached all three to every winner.
        $this->seedEndingPkEvent([
            [1, 'pk-king', 500],
            [1, 'pk-star', 300],
            [1, 'pk-room', 200],
        ]);

        // roomowner_id=0 so there is no pk-room winner at all.
        $this->sendPkGift($sender->id, $receiver->id, 0, 4000);

        Artisan::call('pk-event-winner');

        // pk-king winner: exactly the 500 pk-king reward.
        $this->assertSame((float) 500, $this->di($sender->id), 'pk-king winner must receive only the pk-king reward');
        // pk-star winner: exactly the 300 pk-star reward.
        $this->assertSame((float) 300, $this->di($receiver->id), 'pk-star winner must receive only the pk-star reward');

        // Reward links match category rewards only (1 link each, not 3).
        foreach ([[$sender->id, 'pk-king'], [$receiver->id, 'pk-star']] as [$uid, $type]) {
            $links = DB::table('reward_winner_pks')
                ->join('pk_winners', 'pk_winners.id', '=', 'reward_winner_pks.pk_winner_id')
                ->join('pk_rewards', 'pk_rewards.id', '=', 'reward_winner_pks.pk_reward_id')
                ->where('pk_winners.user_id', $uid)
                ->get(['pk_rewards.pk_type']);
            $this->assertCount(1, $links, "user {$uid}: exactly one reward link");
            $this->assertSame($type, $links[0]->pk_type, "user {$uid}: reward link must be of own category");
        }
    }

    public function test_roomowner_zero_rows_excluded_from_pk_room_ranking(): void
    {
        $sender = $this->makeUser(['di' => 0]);
        $receiver = $this->makeUser(['di' => 0]);
        $realOwner = $this->makeUser(['di' => 0]);

        $this->seedEndingPkEvent([
            [1, 'pk-room', 200],
            [2, 'pk-room', 100],
        ]);

        // Highest volume has roomowner_id=0 — must NOT produce a rank-1 room winner.
        $this->sendPkGift($sender->id, $receiver->id, 0, 90000);
        // Real room owner with lower volume must take rank 1.
        $this->sendPkGift($sender->id, $receiver->id, $realOwner->id, 1000);

        Artisan::call('pk-event-winner');

        $roomWinners = DB::table('pk_winners')->where('pk_type', 'pk-room')->get();
        $this->assertCount(1, $roomWinners, 'only real room owners may be crowned');
        $this->assertSame($realOwner->id, (int) $roomWinners[0]->user_id);
        $this->assertSame(1, (int) $roomWinners[0]->level, 'the zero row must not shift the real owner to rank 2');
        $this->assertSame((float) 200, $this->di($realOwner->id));
        $this->assertSame(0, DB::table('pk_winners')->where('user_id', 0)->count(), 'user_id=0 must never be crowned');
    }

    public function test_unique_event_user_type_enforced_at_db_layer(): void
    {
        $pkEventId = $this->seedEndingPkEvent([[1, 'pk-king', 100]]);

        DB::table('pk_winners')->insert([
            'pk_event_id' => $pkEventId, 'user_id' => 42, 'level' => 1, 'pk_type' => 'pk-king',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Same user, different category => allowed.
        DB::table('pk_winners')->insert([
            'pk_event_id' => $pkEventId, 'user_id' => 42, 'level' => 1, 'pk_type' => 'pk-star',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('pk_winners')->insert([
            'pk_event_id' => $pkEventId, 'user_id' => 42, 'level' => 2, 'pk_type' => 'pk-king',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_partial_failure_rolls_back_and_rerun_recovers(): void
    {
        $sender = $this->makeUser(['di' => 0]);
        $receiver = $this->makeUser(['di' => 0]);

        $this->seedEndingPkEvent([[1, 'pk-king', 500]]);
        $this->sendPkGift($sender->id, $receiver->id, 0, 4000);

        $this->armDiIncrementSabotage();
        try {
            Artisan::call('pk-event-winner');
            $this->fail('sabotage exception should have propagated');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('QA sabotage', $e->getMessage());
        } finally {
            $this->disarmSabotage();
        }

        $this->assertSame(0, DB::table('pk_winners')->where('user_id', $sender->id)->count(), 'winner row must roll back');
        $this->assertSame(0, $this->coinLogCount($sender->id, 'pk_event'), 'coin log must roll back');
        $this->assertSame(0.0, $this->di($sender->id), 'di must roll back');

        Artisan::call('pk-event-winner');
        $this->assertSame(1, DB::table('pk_winners')->where('user_id', $sender->id)->where('pk_type', 'pk-king')->count());
        $this->assertSame((float) 500, $this->di($sender->id));
    }

    public function test_same_user_can_win_king_and_star_but_each_once(): void
    {
        // User sends to himself-like flow: he is top sender AND top receiver.
        $u = $this->makeUser(['di' => 0]);
        $other = $this->makeUser(['di' => 0]);

        $this->seedEndingPkEvent([
            [1, 'pk-king', 500],
            [1, 'pk-star', 300],
        ]);

        // $u tops the sender ranking; $u also tops the receiver ranking.
        $this->sendPkGift($u->id, $u->id, 0, 5000);
        $this->sendPkGift($other->id, $u->id, 0, 100);

        Artisan::call('pk-event-winner');
        Artisan::call('pk-event-winner');

        $this->assertSame(1, DB::table('pk_winners')->where('user_id', $u->id)->where('pk_type', 'pk-king')->count());
        $this->assertSame(1, DB::table('pk_winners')->where('user_id', $u->id)->where('pk_type', 'pk-star')->count());
        $this->assertSame((float) (500 + 300), $this->di($u->id), 'one payout per category, none duplicated');
    }
}