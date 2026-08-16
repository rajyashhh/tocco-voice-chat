<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/**
 * PK Event & Weekly Star API (plan item 5).
 *
 * Code under test:
 *   Modules/Events/Http/Controllers/PkEventController.php (pkEvent / topUsersPKEvent)
 *   Modules/Events/Http/Controllers/WeeklyStarController.php (topUsersEvent)
 *
 * Acceptance criteria:
 *  - pkEvent: winner_previous_event reads from pk_winners (level=1 per
 *    category) of the previous event; 422 when no current event.
 *  - topUsersPKEvent type=3 (rooms): roomowner_id=0 rows never appear.
 *  - weekly topUsersEvent: only tracked gifts inside the window count.
 */
class PkWeeklyApiTest extends EventsQaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckLatestToken::class,
            \App\Http\Middleware\GeneralBanMiddleware::class,
            \App\Http\Middleware\UpdateLastSeen::class,
            \App\Http\Middleware\CheckCpu::class,
        ]);
    }

    private function seedPkEvent(string $start, string $end, array $rewards = []): int
    {
        $id = DB::table('pk_events')->insertGetId([
            'start_date' => $start, 'end_date' => $end,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($rewards as [$level, $pkType, $coins]) {
            DB::table('pk_rewards')->insert([
                'pk_event_id' => $id, 'type' => 'coins', 'level' => $level,
                'target' => (string) $coins, 'pk_type' => $pkType, 'expire' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $id;
    }

    public function test_pk_event_endpoint_previous_winners_come_from_pk_winners_table(): void
    {
        $viewer = $this->makeUser();
        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        // Previous event that ended (previousEvent scope) with crowned winners.
        $prevId = $this->seedPkEvent(
            Carbon::now()->subWeeks(2)->toDateString(),
            Carbon::now()->subWeek()->toDateString()
        );
        DB::table('pk_winners')->insert([
            ['pk_event_id' => $prevId, 'user_id' => $sender->id, 'level' => 1, 'pk_type' => 'pk-king', 'created_at' => now(), 'updated_at' => now()],
            ['pk_event_id' => $prevId, 'user_id' => $receiver->id, 'level' => 1, 'pk_type' => 'pk-star', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Current active event.
        $this->seedPkEvent(
            Carbon::now()->subDay()->toDateString(),
            Carbon::now()->addDays(6)->toDateString()
        );

        // winner->profile is hasOne => resolves the FIRST profile row; make its
        // avatar deterministic (UserObserver creates an avatar-less profile
        // before the factory's one).
        DB::table('profiles')->where('user_id', $sender->id)->update(['avatar' => 'qa/king.jpg']);
        DB::table('profiles')->where('user_id', $receiver->id)->update(['avatar' => 'qa/star.jpg']);

        Sanctum::actingAs($viewer);
        $res = $this->getJson('api/pk-events/pk-event');
        $res->assertStatus(200);

        $this->assertSame('qa/king.jpg', $res->json('data.winner_previous_event.PK_king'), 'pk-king previous winner must resolve from pk_winners');
        $this->assertSame('qa/star.jpg', $res->json('data.winner_previous_event.PK_star'), 'pk-star previous winner must resolve from pk_winners');
    }

    public function test_pk_event_endpoint_422_when_no_current_event(): void
    {
        $viewer = $this->makeUser();
        Sanctum::actingAs($viewer);

        $res = $this->getJson('api/pk-events/pk-event');
        $res->assertStatus(422);
    }

    public function test_top_pk_rooms_excludes_roomowner_zero(): void
    {
        $viewer = $this->makeUser();
        $sender = $this->makeUser();
        $receiver = $this->makeUser();
        $owner = $this->makeUser();

        $this->seedPkEvent(
            Carbon::now()->subDay()->toDateString(),
            Carbon::now()->addDays(6)->toDateString()
        );

        // Zero-room row with huge volume + real room owner with small volume.
        $this->insertGiftLog([
            'giftId' => 1, 'sender_id' => $sender->id, 'receiver_id' => $receiver->id,
            'roomowner_id' => 0, 'giftPrice' => 90000, 'pk' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->insertGiftLog([
            'giftId' => 1, 'sender_id' => $sender->id, 'receiver_id' => $receiver->id,
            'roomowner_id' => $owner->id, 'giftPrice' => 500, 'pk' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Sanctum::actingAs($viewer);
        $res = $this->postJson('api/pk-events/top-pk-events', ['type' => 3]);
        $res->assertStatus(200);

        // PkEventTopResource: 'id' = user id, 'user_id' = uuid.
        $ownerIds = collect($res->json('data.top'))->pluck('id')->map(fn ($v) => (int) $v)->values();
        $this->assertFalse($ownerIds->contains(0), 'roomowner_id=0 must never rank');
        $this->assertTrue($ownerIds->contains($owner->id), 'the real room owner must rank. body: ' . $res->getContent());
    }

    public function test_weekly_top_counts_only_tracked_gifts_within_window(): void
    {
        $viewer = $this->makeUser();
        $inWindow = $this->makeUser();
        $wrongGift = $this->makeUser();
        $outOfWindow = $this->makeUser();

        $tracked = $this->insertGift(10);
        $untracked = $this->insertGift(10);

        $wsId = DB::table('weekly_stars')->insertGetId([
            'start_date' => Carbon::now()->subDays(3)->toDateString(),
            'end_date' => Carbon::now()->addDays(4)->toDateString(),
            'type' => 'weekly_star',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('weekly_star_gifts')->insert(['weekly_star_id' => $wsId, 'gift_id' => $tracked]);

        $this->insertGiftLog(['giftId' => $tracked, 'sender_id' => $inWindow->id, 'receiver_id' => 1, 'giftPrice' => 700, 'created_at' => now(), 'updated_at' => now()]);
        $this->insertGiftLog(['giftId' => $untracked, 'sender_id' => $wrongGift->id, 'receiver_id' => 1, 'giftPrice' => 9000, 'created_at' => now(), 'updated_at' => now()]);
        $this->insertGiftLog(['giftId' => $tracked, 'sender_id' => $outOfWindow->id, 'receiver_id' => 1, 'giftPrice' => 8000, 'created_at' => Carbon::now()->subWeeks(2), 'updated_at' => Carbon::now()->subWeeks(2)]);

        Sanctum::actingAs($viewer);
        $res = $this->getJson('api/events/top-weekly-events');
        $res->assertStatus(200);

        $senderIds = collect($res->json('data.top'))->pluck('user_id')
            ->filter()->map(fn ($v) => (int) $v)->values();

        $this->assertTrue($senderIds->contains($inWindow->id), 'in-window tracked-gift sender must appear. body: ' . $res->getContent());
        $this->assertFalse($senderIds->contains($wrongGift->id), 'untracked gift must not count');
        $this->assertFalse($senderIds->contains($outOfWindow->id), 'out-of-window log must not count');
    }

    public function test_winner_notifications_fire_once_per_winner_not_per_rerun(): void
    {
        $sender = $this->makeUser(['di' => 0]);
        $receiver = $this->makeUser(['di' => 0]);

        $pkId = $this->seedPkEvent(
            Carbon::now()->subWeek()->toDateString(),
            Carbon::now()->toDateString(),
            [[1, 'pk-king', 100], [1, 'pk-star', 100]]
        );

        $this->insertGiftLog([
            'giftId' => 1, 'sender_id' => $sender->id, 'receiver_id' => $receiver->id,
            'roomowner_id' => 0, 'giftPrice' => 4000, 'pk' => 1,
            'created_at' => Carbon::now()->subDays(2), 'updated_at' => Carbon::now()->subDays(2),
        ]);

        Artisan::call('pk-event-winner');
        Artisan::call('pk-event-winner');

        // Exactly-once notification per (winner, category), no repeats on rerun.
        $this->notifySpy->shouldHaveReceived('pkEventWinner')->twice();
    }
}