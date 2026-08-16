<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Modules\Events\Repositories\ChargeKingRepository;

/**
 * ChargeKingRepository unit-level checks (plan item 6). Runs against the QA
 * MySQL schema (the repository is raw query builder — no seams to mock, and
 * the SQL itself is what needs proving).
 *
 * Covers: totalFor window edges, rankOf tie semantics, nextHigherTotal,
 * top() zero-total exclusion.
 */
class ChargeKingRepositoryUnitTest extends EventsQaTestCase
{
    private ChargeKingRepository $repo;

    private string $from;

    private string $till;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ChargeKingRepository();
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->till = Carbon::now()->endOfMonth()->toDateString();
    }

    public function test_total_for_sums_charges_and_status1_coin_logs_within_window(): void
    {
        $u = $this->makeUser();
        $in = Carbon::now()->startOfMonth()->addDays(1);

        $this->insertCharge($u->id, 100, $in);
        $this->insertCoinLog($u->id, 50, $in, 1);
        $this->insertCoinLog($u->id, 999, $in, 0);                             // status 0
        $this->insertCoinLog($u->id, 888, $in, 1, 'App\\Models\\Agency');      // wrong morph
        $this->insertCharge($u->id, 777, Carbon::now()->subMonths(2));         // outside window

        $this->assertSame(150, $this->repo->totalFor($u->id, $this->from, $this->till));
    }

    public function test_total_for_includes_boundary_days_of_window(): void
    {
        $u = $this->makeUser();

        // First minute of the from-day and last minute of the till-day.
        $this->insertCharge($u->id, 10, $this->from . ' 00:00:30');
        $this->insertCharge($u->id, 20, $this->till . ' 23:59:00');

        $this->assertSame(30, $this->repo->totalFor($u->id, $this->from, $this->till));
    }

    public function test_rank_of_counts_strictly_higher_totals_ties_share_rank(): void
    {
        $in = Carbon::now()->startOfMonth()->addDays(1);
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();

        $this->insertCharge($a->id, 300, $in);
        $this->insertCharge($b->id, 200, $in);
        $this->insertCharge($c->id, 200, $in);

        $this->assertSame(1, $this->repo->rankOf(300, $this->from, $this->till));
        // Both 200-totals rank 2 (one strictly-higher total).
        $this->assertSame(2, $this->repo->rankOf(200, $this->from, $this->till));
        // Three user-rows (300, 200, 200) are strictly higher than 100 => rank 4.
        $this->assertSame(4, $this->repo->rankOf(100, $this->from, $this->till));
    }

    public function test_rank_of_zero_or_negative_total_is_null(): void
    {
        $this->assertNull($this->repo->rankOf(0, $this->from, $this->till));
        $this->assertNull($this->repo->rankOf(-5, $this->from, $this->till));
    }

    public function test_next_higher_total_returns_score_directly_ahead_or_null_when_leading(): void
    {
        $in = Carbon::now()->startOfMonth()->addDays(1);
        $a = $this->makeUser();
        $b = $this->makeUser();

        $this->insertCharge($a->id, 300, $in);
        $this->insertCharge($b->id, 100, $in);

        $this->assertSame(300, $this->repo->nextHigherTotal(100, $this->from, $this->till));
        $this->assertNull($this->repo->nextHigherTotal(300, $this->from, $this->till), 'leader has nobody ahead');
    }

    public function test_top_excludes_zero_total_users_and_orders_desc(): void
    {
        $in = Carbon::now()->startOfMonth()->addDays(1);
        $a = $this->makeUser();
        $b = $this->makeUser();
        $this->makeUser(); // zero-total user

        $this->insertCharge($a->id, 100, $in);
        $this->insertCharge($b->id, 500, $in);

        $top = $this->repo->top($this->from, $this->till, 10);

        $ids = $top->pluck('id')->all();
        $this->assertSame([$b->id, $a->id], array_slice($ids, 0, 2), 'ordered by total desc');
        foreach ($top as $u) {
            $this->assertGreaterThan(0, (int) $u->total_sum, 'zero-total users are excluded');
        }
    }
}