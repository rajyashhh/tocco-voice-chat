<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/**
 * Charge King API + eligibility filters (plan item 5 + new filters).
 *
 * Code under test:
 *   Modules/Events/Http/Controllers/ChargeEventController.php
 *     (leaderboard / wonEvent / previousWinners / received_rewards)
 *   Modules/Events/Repositories/ChargeKingRepository.php (eligibleQuery filters)
 *
 * Acceptance criteria:
 *  - leaderboard: staff (type_user==3) and actively-banned users never appear;
 *    ranking ordered by charges + status=1 coin_logs within current month;
 *    `me` block returns the caller's own total and rank.
 *  - coin_logs restricted to user morphs (user_type = App\Models\User) and
 *    status=1 rows only.
 *  - wonEvent: reads the rank-1 row of last month from charge_king_winners.
 *  - previousWinners: months grouped, ranks ordered.
 *  - received_rewards: double claim rejected, staff rejected, below-target rejected.
 */
class ChargeKingApiTest extends EventsQaTestCase
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

    public function test_leaderboard_excludes_staff_and_banned_users(): void
    {
        $now = Carbon::now()->startOfMonth()->addDays(2);

        $normal = $this->makeUser();
        $staff = $this->makeUser(['type_user' => 3]);
        $banned = $this->makeUser(['uuid' => 'qa-ban-' . uniqid()]);
        $viewer = $this->makeUser();

        $this->insertCharge($normal->id, 1000, $now);
        $this->insertCharge($staff->id, 90000, $now);
        $this->insertCharge($banned->id, 50000, $now);
        $this->banUser($banned);

        Sanctum::actingAs($viewer);
        $res = $this->getJson('api/charge-events/leaderboard');
        $res->assertStatus(200);

        $ids = collect($res->json('data.top'))->pluck('user_id');
        $this->assertTrue($ids->contains($normal->id), 'eligible charger must appear');
        $this->assertFalse($ids->contains($staff->id), 'staff (type_user=3) must be excluded');
        $this->assertFalse($ids->contains($banned->id), 'actively banned user must be excluded');

        // Sole eligible charger is rank 1.
        $this->assertSame(1, collect($res->json('data.top'))->firstWhere('user_id', $normal->id)['rank']);
    }

    public function test_leaderboard_counts_only_status1_user_coin_logs_and_charges(): void
    {
        $now = Carbon::now()->startOfMonth()->addDays(2);

        $u = $this->makeUser();
        $viewer = $this->makeUser();

        $this->insertCharge($u->id, 1000, $now);                              // counts
        $this->insertCoinLog($u->id, 500, $now, 1);                           // counts
        $this->insertCoinLog($u->id, 9999, $now, 0);                          // status=0: no
        $this->insertCoinLog($u->id, 8888, $now, 1, 'App\\Models\\Agency');   // wrong morph: no
        $this->insertCoinLog($u->id, 7777, Carbon::now()->subMonths(2), 1);   // out of window: no

        Sanctum::actingAs($viewer);
        $res = $this->getJson('api/charge-events/leaderboard');
        $res->assertStatus(200);

        $row = collect($res->json('data.top'))->firstWhere('user_id', $u->id);
        $this->assertNotNull($row);
        $this->assertSame(1500, (int) $row['points'], 'points = charges(1000) + status1 user coin_logs(500) only');
    }

    public function test_leaderboard_me_block_reports_caller_total_and_rank(): void
    {
        $now = Carbon::now()->startOfMonth()->addDays(2);

        $leader = $this->makeUser();
        $me = $this->makeUser();

        $this->insertCharge($leader->id, 5000, $now);
        $this->insertCharge($me->id, 1000, $now);

        Sanctum::actingAs($me);
        $res = $this->getJson('api/charge-events/leaderboard');
        $res->assertStatus(200);

        $this->assertSame(1000, (int) $res->json('data.me.points'));
        $this->assertSame(2, (int) $res->json('data.me.rank'));
    }

    public function test_won_event_returns_last_month_rank1_from_winners_table(): void
    {
        $lastMonth = Carbon::now()->subMonth();

        $king = $this->makeUser();
        $second = $this->makeUser();
        $viewer = $this->makeUser();

        $this->insertCharge($king->id, 9000, $lastMonth->copy()->startOfMonth()->addDays(3));
        $this->insertCharge($second->id, 100, $lastMonth->copy()->startOfMonth()->addDays(3));
        DB::table('charge_king_rewards')->insert([
            'rank' => 1, 'type' => 'coins', 'target' => '500', 'expire' => '0',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Artisan::call('charge-king-winner');

        Sanctum::actingAs($viewer);
        $res = $this->getJson('api/charge-events/won_event');
        $res->assertStatus(200);
        $this->assertSame($king->id, (int) $res->json('data.user_id'), 'wonEvent must surface the crowned rank-1 user');
    }

    public function test_previous_winners_grouped_by_month_ordered_by_rank(): void
    {
        $viewer = $this->makeUser();
        $m1 = Carbon::now()->subMonth()->format('Y-m');
        $m2 = Carbon::now()->subMonths(2)->format('Y-m');

        $winners = [];
        foreach ([[$m1, 1], [$m1, 2], [$m2, 1]] as [$month, $rank]) {
            $u = $this->makeUser();
            $winners["$month-$rank"] = $u->id;
            DB::table('charge_king_winners')->insert([
                'user_id' => $u->id, 'month' => $month, 'rank' => $rank, 'prize' => 100 * $rank,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Sanctum::actingAs($viewer);
        $res = $this->getJson('api/charge-events/previous-winners');
        $res->assertStatus(200);

        $data = collect($res->json('data'));
        $this->assertCount(2, $data, 'two months with winners');

        $first = $data->firstWhere('month', $m1);
        $this->assertNotNull($first);
        $this->assertSame([1, 2], collect($first['winners'])->pluck('rank')->all(), 'ranks ordered within month');
        $this->assertSame($winners["$m1-1"], (int) $first['winners'][0]['user_id']);
    }

    // ------------------------------------------------- received_rewards (targets)

    private function seedTarget(int $value, int $coins = 200): int
    {
        $targetId = DB::table('charge_events')->insertGetId([
            'tile' => 'QA-T', 'value' => $value, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('reward_charges')->insert([
            'charge_event_id' => $targetId, 'type' => 'coins', 'target' => (string) $coins,
            'expire' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $targetId;
    }

    public function test_received_rewards_pays_once_then_rejects_double_claim(): void
    {
        $u = $this->makeUser(['di' => 0]);
        $this->insertCharge($u->id, 1000, Carbon::now()->startOfMonth()->addDay());
        $targetId = $this->seedTarget(500, 200);

        Sanctum::actingAs($u);

        $first = $this->postJson('api/charge-events/received-rewards', ['target_id' => $targetId]);
        $first->assertStatus(200);
        $this->assertTrue((bool) $first->json('success'), 'first claim succeeds: ' . $first->getContent());
        $this->assertSame((float) 200, $this->di($u->id));

        $second = $this->postJson('api/charge-events/received-rewards', ['target_id' => $targetId]);
        $this->assertFalse((bool) $second->json('success'), 'second claim must be rejected');
        $this->assertSame((float) 200, $this->di($u->id), 'no double payout');
        $this->assertSame(1, DB::table('user_charge_events')->where('user_id', $u->id)->where('charge_event_id', $targetId)->count());
        $this->assertSame(1, $this->coinLogCount($u->id, 'charge_event'));
    }

    public function test_received_rewards_rejects_staff_and_below_target(): void
    {
        $targetId = $this->seedTarget(500);

        // Staff account (type_user=3).
        $staff = $this->makeUser(['di' => 0, 'type_user' => 3]);
        $this->insertCharge($staff->id, 5000, Carbon::now()->startOfMonth()->addDay());
        Sanctum::actingAs($staff);
        $res = $this->postJson('api/charge-events/received-rewards', ['target_id' => $targetId]);
        $this->assertFalse((bool) $res->json('success'), 'staff must be rejected');
        $this->assertSame(0.0, $this->di($staff->id));

        // Below target.
        $poor = $this->makeUser(['di' => 0]);
        $this->insertCharge($poor->id, 100, Carbon::now()->startOfMonth()->addDay());
        Sanctum::actingAs($poor);
        $res2 = $this->postJson('api/charge-events/received-rewards', ['target_id' => $targetId]);
        $this->assertFalse((bool) $res2->json('success'), 'below-target must be rejected');
        $this->assertSame(0.0, $this->di($poor->id));

        // Unknown target id.
        $res3 = $this->postJson('api/charge-events/received-rewards', ['target_id' => 99999999]);
        $this->assertFalse((bool) $res3->json('success'), 'unknown target must be rejected');
    }
}