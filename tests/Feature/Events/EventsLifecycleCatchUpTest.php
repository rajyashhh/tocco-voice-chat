<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle & catch-up (plan items 2.1–2.5 / 3.1–3.2).
 *
 * Code under test:
 *   Modules/Events/Console/WeeklyStarUpdate.php  (catch-up while loop)
 *   Modules/Events/Console/PkEventCommand.php    (catchUpMissedRounds)
 *
 * Acceptance criteria:
 *  - Normal rollover: when a round ends, the next round is created once with
 *    start = previous end, end = start + 1 week, and gifts/rewards are copied.
 *  - Idempotent rollover: running the update command twice creates no
 *    duplicate rounds.
 *  - Catch-up: with the scheduler dead for 2+ weeks, one run backfills every
 *    missed week exactly once, chained contiguously, and the current date is
 *    covered by an active round.
 */
class EventsLifecycleCatchUpTest extends EventsQaTestCase
{
    // ---------------------------------------------------------------- weekly

    private function seedWeeklyStar(string $start, string $end, array $giftIds = [], array $rewards = []): int
    {
        $id = DB::table('weekly_stars')->insertGetId([
            'start_date' => $start, 'end_date' => $end, 'type' => 'weekly_star',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($giftIds as $g) {
            DB::table('weekly_star_gifts')->insert(['weekly_star_id' => $id, 'gift_id' => $g]);
        }
        foreach ($rewards as [$level, $coins]) {
            DB::table('rewards')->insert([
                'weekly_star_id' => $id, 'type' => 'coins', 'level' => $level,
                'target' => (string) $coins, 'expire' => 7,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $id;
    }

    public function test_2_1_weekly_rollover_creates_next_round_with_gifts_and_rewards(): void
    {
        $g1 = $this->insertGift(10);
        $g2 = $this->insertGift(20);
        $today = Carbon::now()->toDateString();
        $this->seedWeeklyStar(Carbon::now()->subWeek()->toDateString(), $today, [$g1, $g2], [[1, 100], [2, 50]]);

        Artisan::call('weekly-star-update');

        $next = DB::table('weekly_stars')->where('type', 'weekly_star')
            ->whereDate('start_date', $today)->first();
        $this->assertNotNull($next, 'a new round starting today must exist');
        $this->assertSame(
            Carbon::parse($today)->addWeek()->toDateString(),
            Carbon::parse($next->end_date)->toDateString(),
            'new round must span exactly one week'
        );

        $gifts = DB::table('weekly_star_gifts')->where('weekly_star_id', $next->id)->pluck('gift_id');
        $this->assertEqualsCanonicalizing([$g1, $g2], $gifts->map(fn ($g) => (int) $g)->all(), 'gifts must be copied');

        $rewards = DB::table('rewards')->where('weekly_star_id', $next->id)->get();
        $this->assertCount(2, $rewards, 'rewards must be copied');
        $this->assertEqualsCanonicalizing([100, 50], $rewards->pluck('target')->map(fn ($t) => (int) $t)->all());
    }

    public function test_2_2_weekly_rollover_is_idempotent(): void
    {
        $g1 = $this->insertGift(10);
        $today = Carbon::now()->toDateString();
        $this->seedWeeklyStar(Carbon::now()->subWeek()->toDateString(), $today, [$g1], [[1, 100]]);

        Artisan::call('weekly-star-update');
        Artisan::call('weekly-star-update');
        Artisan::call('weekly-star-update');

        $this->assertSame(
            1,
            DB::table('weekly_stars')->where('type', 'weekly_star')->whereDate('start_date', $today)->count(),
            'no duplicate round for the same start date'
        );
    }

    public function test_2_3_weekly_catch_up_backfills_two_missed_weeks_without_duplicates(): void
    {
        // Scheduler died: last round ended 2 weeks ago.
        $g1 = $this->insertGift(10);
        $lastEnd = Carbon::now()->subWeeks(2)->toDateString();
        $this->seedWeeklyStar(Carbon::now()->subWeeks(3)->toDateString(), $lastEnd, [$g1], [[1, 100]]);

        Artisan::call('weekly-star-update');

        $rounds = DB::table('weekly_stars')->where('type', 'weekly_star')
            ->orderBy('start_date')->get(['id', 'start_date', 'end_date']);

        // Original + one per missed week boundary (lastEnd, lastEnd+1w, lastEnd+2w=today).
        $expectedStarts = [
            Carbon::now()->subWeeks(3)->toDateString(),
            Carbon::parse($lastEnd)->toDateString(),
            Carbon::parse($lastEnd)->addWeek()->toDateString(),
            Carbon::parse($lastEnd)->addWeeks(2)->toDateString(),
        ];
        $this->assertSame(
            $expectedStarts,
            $rounds->pluck('start_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all(),
            'every missed week must be backfilled exactly once, contiguously'
        );

        // Chain integrity: each round starts where the previous ended.
        for ($i = 1; $i < count($rounds); $i++) {
            $this->assertSame(
                Carbon::parse($rounds[$i - 1]->end_date)->toDateString(),
                Carbon::parse($rounds[$i]->start_date)->toDateString(),
                "round {$i} must start at the previous round's end"
            );
        }

        // Today is covered.
        $today = Carbon::now()->toDateString();
        $covering = $rounds->first(fn ($r) => Carbon::parse($r->start_date)->toDateString() <= $today
            && Carbon::parse($r->end_date)->toDateString() >= $today);
        $this->assertNotNull($covering, 'an active round must cover today after catch-up');

        // Gifts propagated down the chain to the newest round.
        $newest = $rounds->last();
        $this->assertSame(1, DB::table('weekly_star_gifts')->where('weekly_star_id', $newest->id)->count());
        $this->assertSame(1, DB::table('rewards')->where('weekly_star_id', $newest->id)->count());

        // Second run adds nothing.
        Artisan::call('weekly-star-update');
        $this->assertSame($rounds->count(), DB::table('weekly_stars')->where('type', 'weekly_star')->count());
    }

    // -------------------------------------------------------------------- pk

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

    public function test_3_1_pk_rollover_creates_next_round_and_copies_rewards(): void
    {
        $today = Carbon::now()->toDateString();
        $this->seedPkEvent(Carbon::now()->subWeek()->toDateString(), $today, [
            [1, 'pk-king', 500], [1, 'pk-star', 300],
        ]);

        Artisan::call('pk-event-update');

        $next = DB::table('pk_events')->whereDate('start_date', $today)->first();
        $this->assertNotNull($next, 'a new PK round starting today must exist');
        $this->assertSame(
            Carbon::parse($today)->addWeek()->toDateString(),
            Carbon::parse($next->end_date)->toDateString()
        );

        $rewards = DB::table('pk_rewards')->where('pk_event_id', $next->id)->get();
        $this->assertCount(2, $rewards, 'rewards must be copied with pk_type preserved');
        $this->assertEqualsCanonicalizing(['pk-king', 'pk-star'], $rewards->pluck('pk_type')->all());
    }

    public function test_3_1b_pk_rollover_is_idempotent(): void
    {
        $today = Carbon::now()->toDateString();
        $this->seedPkEvent(Carbon::now()->subWeek()->toDateString(), $today, [[1, 'pk-king', 500]]);

        Artisan::call('pk-event-update');
        Artisan::call('pk-event-update');

        $this->assertSame(1, DB::table('pk_events')->whereDate('start_date', $today)->count());
    }

    public function test_3_2_pk_catch_up_backfills_missed_weeks_without_duplicates(): void
    {
        $lastEnd = Carbon::now()->subWeeks(2)->toDateString();
        $this->seedPkEvent(Carbon::now()->subWeeks(3)->toDateString(), $lastEnd, [[1, 'pk-king', 500]]);

        Artisan::call('pk-event-update');

        $rounds = DB::table('pk_events')->orderBy('start_date')->get(['id', 'start_date', 'end_date']);

        // catchUpMissedRounds loops while cursorEnd < today, i.e. it backfills
        // up to and including the round that ENDS today; the round STARTING
        // today is produced by the normal endToday rollover on the next
        // invocation (verified below). Coverage stays continuous either way.
        $expectedStarts = [
            Carbon::now()->subWeeks(3)->toDateString(),
            Carbon::parse($lastEnd)->toDateString(),
            Carbon::parse($lastEnd)->addWeek()->toDateString(),
        ];
        $this->assertSame(
            $expectedStarts,
            $rounds->pluck('start_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all(),
            'PK catch-up must backfill each missed week exactly once'
        );

        for ($i = 1; $i < count($rounds); $i++) {
            $this->assertSame(
                Carbon::parse($rounds[$i - 1]->end_date)->toDateString(),
                Carbon::parse($rounds[$i]->start_date)->toDateString()
            );
        }

        // Today must be covered by the newest backfilled round (ends today).
        $today = Carbon::now()->toDateString();
        $newest = $rounds->last();
        $this->assertTrue(
            Carbon::parse($newest->start_date)->toDateString() <= $today
                && Carbon::parse($newest->end_date)->toDateString() >= $today,
            'the newest backfilled round must cover today'
        );

        // Rewards propagated to the newest round.
        $this->assertSame(1, DB::table('pk_rewards')->where('pk_event_id', $newest->id)->count());

        // Second run: the newest round ends today, so the normal rollover now
        // creates exactly one round starting today — and nothing else.
        Artisan::call('pk-event-update');
        $this->assertSame(4, DB::table('pk_events')->count());
        $this->assertSame(1, DB::table('pk_events')->whereDate('start_date', $today)->count());
    }

    /**
     * DEFECT DOCUMENTATION (expected to FAIL until fixed):
     *
     * PkEventCommand::handle() rollover branch:
     *   $pkEvent = PkEvent::endToday()->first();          // matches ALL DAY on end-date day
     *   $lastEndDate = PkEvent::max('end_date');          // GLOBAL max, not $pkEvent->end_date
     *   $newStartDate = lastEndDate; create if no round starts there.
     *
     * On the day a round ends, run 1 creates the round starting today (chained
     * from max=today). Run 2 the same day: endToday still matches, max(end_date)
     * is now today+7, so it creates ANOTHER round starting today+7. Run 3
     * creates today+14, and so on — one unwanted FUTURE round per extra run.
     *
     * Contrast: WeeklyStarUpdate is bounded by `if ($cursor > $today) return`.
     *
     * Expected behaviour asserted here: re-running the command on the same day
     * must not create rounds beyond the one starting today.
     */
    public function test_3_2c_pk_rollover_rerun_on_end_day_must_not_mint_future_rounds(): void
    {
        $today = Carbon::now()->toDateString();
        $this->seedPkEvent(Carbon::now()->subWeek()->toDateString(), $today, [[1, 'pk-king', 500]]);

        Artisan::call('pk-event-update'); // creates round starting today (correct)
        Artisan::call('pk-event-update'); // must be a no-op
        Artisan::call('pk-event-update'); // must be a no-op

        $futureRounds = DB::table('pk_events')
            ->whereDate('start_date', '>', $today)
            ->count();

        $this->assertSame(
            0,
            $futureRounds,
            'rerunning pk-event-update on an end day must not create future rounds '
            . '(defect: rollover chains newStartDate off global max(end_date) while endToday still matches)'
        );
        $this->assertSame(2, DB::table('pk_events')->count(), 'exactly: the ended round + the one starting today');
    }

    public function test_3_2b_pk_catch_up_noop_when_current_round_active(): void
    {
        // Active round covering today: catch-up must not fire.
        $this->seedPkEvent(Carbon::now()->subDays(2)->toDateString(), Carbon::now()->addDays(5)->toDateString(), [[1, 'pk-king', 500]]);

        Artisan::call('pk-event-update');

        $this->assertSame(1, DB::table('pk_events')->count(), 'no extra rounds while one is active');
    }
}