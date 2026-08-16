<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Tik\Repositories\UserRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regression guard for the search-user-agency 500 introduced by commit 3c95dd12b6,
 * where filterUserNew() listed model accessor names (total_received_diamonds, ...)
 * inside ->select(), which are NOT real columns -> SQLSTATE[42S22] Unknown column.
 *
 * Runs against the existing app_test_phase1 test DB (no RefreshDatabase: full schema
 * migration is not reproducible locally, but the real users table is present).
 */
class SearchUserAgencyRegressionTest extends TestCase
{
    /**
     * The broken SELECT listed a non-existent column, so MySQL rejected the query
     * field list on EVERY call — even one that matches zero rows. Proving a
     * non-matching prefix returns an (empty) Collection without throwing is a
     * data-independent proof that the 42S22 regression is gone.
     */
    public function test_filterUserNew_does_not_throw_unknown_column_regression(): void
    {
        $result = (new UserRepository())->filterUserNew('zz_no_match_' . uniqid());

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    /**
     * With select('*') the accessors get their raw source columns and must resolve.
     * Clones an existing valid row (satisfies all NOT NULL constraints) with a known
     * uuid so the prefix search is guaranteed to match; cleans up afterwards.
     */
    public function test_accessors_resolve_from_source_columns(): void
    {
        $base = (array) DB::table('users')->first();
        if (!$base) {
            $this->markTestSkipped('app_test_phase1 has no users to clone');
        }

        unset($base['id']);
        $base['uuid'] = 'TST' . substr((string) uniqid(), -7);
        $base['special_id'] = null;

        try {
            $id = DB::table('users')->insertGetId($base);
        } catch (\Throwable $e) {
            $this->markTestSkipped('could not clone a user row: ' . $e->getMessage());
        }

        try {
            $result = (new UserRepository())->filterUserNew(substr($base['uuid'], 0, 4));
            $first = $result->firstWhere('uuid', $base['uuid']);

            $this->assertNotNull($first, 'cloned user must be returned by the prefix search');
            $this->assertEquals(
                (int) (($first->total_diamond_received ?? 0) + ($first->sub_receiver_num ?? 0)),
                (int) $first->total_received_diamonds
            );
            $this->assertEquals(
                (int) (($first->total_diamond_send ?? 0) + ($first->sub_sender_num ?? 0)),
                (int) $first->total_sender_diamonds
            );
        } finally {
            DB::table('users')->where('id', $id)->delete();
        }
    }
}
