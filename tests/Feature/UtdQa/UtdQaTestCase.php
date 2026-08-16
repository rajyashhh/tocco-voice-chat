<?php

namespace Tests\Feature\UtdQa;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Shared base for the UTD QA regression guards (C1 / W1 / W2 / H1 / H2).
 *
 * DB strategy
 * -----------
 * These guards cover money accounting and tenant isolation, so they must run
 * against a REAL MySQL/MariaDB connection: only there do lockForUpdate(),
 * DECIMAL arithmetic, ENUM columns and the UNIQUE(operation_uuid, type) index
 * behave the way production does. Proving "re-check under a row lock" on sqlite
 * would be a false positive, since sqlite has no row-level locking.
 *
 * The suite talks to the dedicated `meow_qa_test` schema (see QA_TEST_DB_SETUP
 * note in the test-run report). Each test wraps its work in a transaction that
 * is rolled back on teardown (DatabaseTransactions), so the schema is never
 * mutated and tests never see each other's rows.
 *
 * If the connection is not MySQL, or the schema has not been provisioned, the
 * individual tests skip with a clear reason instead of reporting a false green.
 */
abstract class UtdQaTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'UTD QA money/isolation guards require a real MySQL/MariaDB connection '
                . '(row locks, DECIMAL, ENUM, unique index). Current driver: '
                . DB::connection()->getDriverName()
            );
        }

        foreach (['users', 'agencies', 'users_wallets', 'wallet_logs'] as $t) {
            if (!Schema::hasTable($t)) {
                $this->markTestSkipped("required table `{$t}` missing in the test DB; provision meow_qa_test first.");
            }
        }
    }

    /**
     * Minimal valid user row via the existing factory.
     */
    protected function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    /**
     * Insert an agency row directly (the Eloquent models carry heavy global
     * scopes / observers we don't want in a fixture) and return its id.
     */
    protected function insertAgency(array $overrides = []): int
    {
        $row = array_merge([
            'name'          => 'QA-AG-' . uniqid(),
            'app_owner_id'  => 0,
            'type'          => 1,
            'is_frozen'     => 0,
            'coins'         => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ], $overrides);

        return DB::table('agencies')->insertGetId($row);
    }
}
