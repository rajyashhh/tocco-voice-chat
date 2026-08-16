<?php

namespace Tests\Unit\Console;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves the deterministic recalc formulas of users:recalc-follow-counters:
 *   number_of_fans       = COUNT(follows WHERE followed_user_id = user)
 *   number_of_followings = COUNT(follows WHERE user_id = user)
 *   number_of_friends    = mutual edges with status=1 on BOTH directions
 * and that poisoned/negative legacy-era values in the number_of_* columns
 * are overwritten from the follows table.
 */
class RecalcFollowCountersTest extends TestCase
{
    use RefreshDatabase;

    private function follow(int $from, int $to, int $status = 1): void
    {
        Follow::query()->create([
            'user_id' => $from,
            'followed_user_id' => $to,
            'status' => $status,
        ]);
    }

    public function test_recalc_rewrites_counters_from_follows(): void
    {
        $a = User::factory()->create(['id' => 9001]);
        $b = User::factory()->create(['id' => 9002]);
        $c = User::factory()->create(['id' => 9003]);

        // a <-> b mutual (friends), c -> a one-way
        $this->follow($a->id, $b->id);
        $this->follow($b->id, $a->id);
        $this->follow($c->id, $a->id);

        User::query()->whereIn('id', [$a->id, $b->id, $c->id])->update([
            'number_of_fans' => 77,
            'number_of_followings' => 77,
            'number_of_friends' => 77,
        ]);

        $this->artisan('users:recalc-follow-counters')->assertExitCode(0);

        $a->refresh();
        $b->refresh();
        $c->refresh();

        $this->assertSame(2, (int) $a->number_of_fans);
        $this->assertSame(1, (int) $a->number_of_followings);
        $this->assertSame(1, (int) $a->number_of_friends);

        $this->assertSame(1, (int) $b->number_of_fans);
        $this->assertSame(1, (int) $b->number_of_followings);
        $this->assertSame(1, (int) $b->number_of_friends);

        $this->assertSame(0, (int) $c->number_of_fans);
        $this->assertSame(1, (int) $c->number_of_followings);
        $this->assertSame(0, (int) $c->number_of_friends);
    }

    public function test_friend_requires_status_one_on_both_edges(): void
    {
        $a = User::factory()->create(['id' => 9011]);
        $b = User::factory()->create(['id' => 9012]);

        $this->follow($a->id, $b->id, 1);
        $this->follow($b->id, $a->id, 0);

        $this->artisan('users:recalc-follow-counters')->assertExitCode(0);

        $this->assertSame(0, (int) $a->refresh()->number_of_friends);
        $this->assertSame(0, (int) $b->refresh()->number_of_friends);
    }

    public function test_fans_count_ignores_status_to_match_followers_relation(): void
    {
        $a = User::factory()->create(['id' => 9021]);
        $b = User::factory()->create(['id' => 9022]);

        $this->follow($b->id, $a->id, 0);

        $this->artisan('users:recalc-follow-counters')->assertExitCode(0);

        $this->assertSame(1, (int) $a->refresh()->number_of_fans);
        $this->assertSame(1, (int) $b->refresh()->number_of_followings);
    }

    public function test_chunked_run_covers_sparse_id_ranges(): void
    {
        $a = User::factory()->create(['id' => 100]);
        $b = User::factory()->create(['id' => 99999]);

        $this->follow($a->id, $b->id);

        User::query()->whereIn('id', [$a->id, $b->id])->update([
            'number_of_fans' => 5,
            'number_of_followings' => 5,
        ]);

        $this->artisan('users:recalc-follow-counters', ['--chunk' => 50])->assertExitCode(0);

        $this->assertSame(1, (int) $a->refresh()->number_of_followings);
        $this->assertSame(1, (int) $b->refresh()->number_of_fans);
        $this->assertSame(0, (int) $a->refresh()->number_of_fans);
    }
}
