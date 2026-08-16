<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Charge King — financial idempotency & atomicity (plan items 1.3 / 1.4 / 1.10).
 *
 * Code under test:
 *   Modules/Events/Console/ChargeKingWinnerCommand.php
 *   Modules/Events/Repositories/ChargeKingRepository.php
 *   Modules/Events/Database/Migrations/2026_08_12_000002_add_rank_to_charge_king_winners_table.php
 *
 * Acceptance criteria:
 *  (1.3) Running `charge-king-winner` N times for the same month pays each of
 *        the top-3 exactly once: one charge_king_winners row per (month,rank),
 *        one CHARGE_EVENT user_coin_log per winner, di increased exactly once.
 *  (1.4) UNIQUE(month,rank) rejects a duplicate insert at the DB layer even if
 *        the app-level exists() check is bypassed.
 *  (1.10) If the payout crashes mid-transaction (after di increment), the
 *        whole unit rolls back: no winner row, no coin log, di unchanged —
 *        so a rerun pays cleanly with no double-accounting.
 */
class ChargeKingIdempotencyTest extends EventsQaTestCase
{
    private function seedTopThree(): array
    {
        $lastMonth = Carbon::now()->subMonth()->startOfMonth()->addDays(3);

        $u1 = $this->makeUser(['di' => 0]);
        $u2 = $this->makeUser(['di' => 0]);
        $u3 = $this->makeUser(['di' => 0]);

        $this->insertCharge($u1->id, 5000, $lastMonth);
        $this->insertCharge($u2->id, 3000, $lastMonth);
        $this->insertCharge($u3->id, 1000, $lastMonth);

        // Coins prizes per rank.
        foreach ([1 => 900, 2 => 500, 3 => 200] as $rank => $coins) {
            DB::table('charge_king_rewards')->insert([
                'rank' => $rank, 'type' => 'coins', 'target' => (string) $coins,
                'expire' => '0', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return [$u1, $u2, $u3];
    }

    public function test_1_3_rerun_does_not_duplicate_payout(): void
    {
        [$u1, $u2, $u3] = $this->seedTopThree();
        $month = Carbon::now()->subMonth()->format('Y-m');

        Artisan::call('charge-king-winner');
        Artisan::call('charge-king-winner');
        Artisan::call('charge-king-winner');

        $rows = DB::table('charge_king_winners')->where('month', $month)->get();
        $this->assertCount(3, $rows, 'exactly one winner row per rank after 3 runs');
        $this->assertSame([1, 2, 3], $rows->pluck('rank')->map(fn ($r) => (int) $r)->sort()->values()->all());

        foreach ([[$u1, 900], [$u2, 500], [$u3, 200]] as [$u, $prize]) {
            $this->assertSame(1, $this->coinLogCount($u->id, 'charge_event'), "user {$u->id}: exactly one coin log");
            $this->assertSame((float) $prize, $this->di($u->id), "user {$u->id}: di paid exactly once");
        }
    }

    public function test_1_3b_rerun_next_day_same_month_still_no_duplicate(): void
    {
        [$u1] = $this->seedTopThree();
        $month = Carbon::now()->subMonth()->format('Y-m');

        Artisan::call('charge-king-winner');

        // Simulate scheduler misfire: rerun after the first run already paid.
        Artisan::call('charge-king-winner');

        $this->assertSame(1, DB::table('charge_king_winners')->where(['month' => $month, 'rank' => 1])->count());
        $this->assertSame((float) 900, $this->di($u1->id));
    }

    public function test_1_4_unique_month_rank_enforced_at_db_layer(): void
    {
        $month = Carbon::now()->subMonth()->format('Y-m');
        DB::table('charge_king_winners')->insert([
            'user_id' => 1, 'month' => $month, 'rank' => 1, 'prize' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('charge_king_winners')->insert([
            'user_id' => 2, 'month' => $month, 'rank' => 1, 'prize' => 20,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_1_10_partial_failure_rolls_back_winner_row_and_coins(): void
    {
        [$u1] = $this->seedTopThree();
        $month = Carbon::now()->subMonth()->format('Y-m');

        $this->armDiIncrementSabotage();
        try {
            Artisan::call('charge-king-winner');
            $this->fail('sabotage exception should have propagated out of the command');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('QA sabotage', $e->getMessage());
        } finally {
            $this->disarmSabotage();
        }

        // Atomicity: rank-1 payout unit fully rolled back.
        $this->assertSame(0, DB::table('charge_king_winners')->where(['month' => $month, 'rank' => 1])->count(), 'winner row must roll back');
        $this->assertSame(0, $this->coinLogCount($u1->id, 'charge_event'), 'coin log must roll back');
        $this->assertSame(0.0, $this->di($u1->id), 'di must roll back');

        // Recovery: rerun pays exactly once.
        Artisan::call('charge-king-winner');
        $this->assertSame(1, DB::table('charge_king_winners')->where(['month' => $month, 'rank' => 1])->count());
        $this->assertSame(1, $this->coinLogCount($u1->id, 'charge_event'));
        $this->assertSame((float) 900, $this->di($u1->id));
    }

    public function test_fallback_prize_from_setting_when_no_rank1_rewards(): void
    {
        $lastMonth = Carbon::now()->subMonth()->startOfMonth()->addDays(3);
        $u1 = $this->makeUser(['di' => 0]);
        $this->insertCharge($u1->id, 5000, $lastMonth);

        DB::table('settings')->insert([
            'key' => 'charge_king_prize', 'value' => '77777',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Artisan::call('charge-king-winner');

        $row = DB::table('charge_king_winners')->where('rank', 1)->first();
        $this->assertNotNull($row);
        $this->assertSame(77777, (int) $row->prize);
        $this->assertSame((float) 77777, $this->di($u1->id));

        // Idempotent under the fallback path too.
        Artisan::call('charge-king-winner');
        $this->assertSame((float) 77777, $this->di($u1->id));
    }
}