<?php

namespace Tests\Feature\UtdQa;

use App\Models\Bd;
use App\Models\Charge;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * WA - Wallet atomicity contract (the money-critical path).
 *
 * Targets:
 *   app/Bd/Controllers/WalletController.php::startTransaction
 *     - salary sufficiency read from a lockForUpdate() row set
 *     - cut distributed across ALL salary rows in ascending id order
 *       (the prior incrementCutAmountInBdSallary applied the whole cut to the
 *        LAST row only, which could push one row's cut_amount past its salary
 *        while the summed check still passed).
 *   Modules/Country/Http/Controllers/SuperAdmin/WalletController.php::chargeToSubAdmin
 *     - both balances on admin_users.di locked in sorted id order, sufficiency
 *       re-read from the locked row before decrement/increment.
 *
 * Concurrency proof boundary
 * --------------------------
 * The base case wraps each test in DatabaseTransactions (rolled back on
 * teardown), so a second real MySQL connection could not see the uncommitted
 * fixtures. True two-connection parallelism (two OS threads serialized on the
 * same locked row) is therefore documented, not asserted here. What IS proven,
 * deterministically, is the INVARIANT the row lock exists to enforce:
 *   (1) the sufficiency check reads the summed/locked balance, so an amount that
 *       exceeds the available balance is rejected with NO money move; and
 *   (2) a second charge after the balance is drained is rejected — the sender
 *       balance can never go negative and the receiver is credited exactly once.
 * If lockForUpdate / the summed re-check were removed, a real double-submit
 * would break these same invariants.
 */
class WalletAtomicityContractTest extends UtdQaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable('bd_salaries')) {
            $this->markTestSkipped('bd_salaries table not provisioned in meow_qa_test.');
        }
    }

    private function makeAdmin(string $type, int $di = 0, ?int $parentId = null): int
    {
        return DB::table('admin_users')->insertGetId(array_filter([
            'username'   => 'qa-wa-' . $type . '-' . uniqid(),
            'password'   => bcrypt('x'),
            'name'       => 'QA-WA',
            'type'       => $type,
            'di'         => $di,
            'parent_id'  => $parentId,
            'country_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ], fn ($v) => $v !== null));
    }

    private function seedBdSalary(int $bdId, string $salary, string $cut = '0'): int
    {
        return DB::table('bd_salaries')->insertGetId([
            'bd_id'      => $bdId,
            'salary'     => $salary,
            'cut_amount' => $cut,
            'month'      => (int) date('m'),
            'year'       => (int) date('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ─────────────── (1) cut is distributed across rows, in order ───────────────

    /**
     * A charge of 50 against two salary rows (30, then 100) must consume the
     * first row fully (cut 30) and the remainder from the second (cut 20). The
     * prior "last row only" behavior would leave row #1 at cut 0 and row #2 at
     * cut 50 — so asserting row #1 == 30 is the exact discriminator.
     */
    public function test_bd_cut_is_distributed_across_rows_in_id_order(): void
    {
        $bdId  = $this->makeAdmin('bd', 0);
        $row1  = $this->seedBdSalary($bdId, '30');   // lower id -> consumed first
        $row2  = $this->seedBdSalary($bdId, '100');
        $rcv   = $this->makeUser(['di' => 0]);

        $sender = Bd::find($bdId);
        Auth::login(\App\Models\Admin::find($bdId));

        $ok = (new \App\Bd\Controllers\WalletController())
            ->startTransaction($rcv->fresh(), $sender, 50, 5000, 'user');

        $this->assertTrue((bool) $ok, 'A sufficient BD charge must succeed.');

        $c1 = (string) DB::table('bd_salaries')->where('id', $row1)->value('cut_amount');
        $c2 = (string) DB::table('bd_salaries')->where('id', $row2)->value('cut_amount');

        $this->assertSame('30.0000', $c1, 'Row #1 must be fully consumed first (cut 30), not left at 0.');
        $this->assertSame('20.0000', $c2, 'Row #2 must absorb only the remainder (cut 20).');

        // No single row cut may exceed its own salary (the defect the fix closes).
        $this->assertTrue(bccomp($c1, '30', 4) <= 0 && bccomp($c2, '100', 4) <= 0,
            'No row cut_amount may exceed that row salary.');

        // Total cut equals the amount charged (once, not doubled).
        $this->assertSame('50.0000', bcadd($c1, $c2, 4), 'Sum of cuts must equal the amount charged exactly once.');

        // Receiver credited exactly the coins.
        $this->assertSame(5000, (int) DB::table('users')->where('id', $rcv->id)->value('di'));
    }

    // ─────────────── (2) over-spend rejected, no money move ───────────────

    /**
     * Amount exceeds the summed available salary -> rejected, no cut applied,
     * receiver not credited. Edge: exactly one coin over the balance.
     */
    public function test_bd_over_spend_is_rejected_with_no_money_move(): void
    {
        $bdId = $this->makeAdmin('bd', 0);
        $row  = $this->seedBdSalary($bdId, '40'); // available = 40
        $rcv  = $this->makeUser(['di' => 0]);

        $sender = Bd::find($bdId);
        Auth::login(\App\Models\Admin::find($bdId));

        try {
            (new \App\Bd\Controllers\WalletController())
                ->startTransaction($rcv->fresh(), $sender, 41, 4100, 'user'); // 41 > 40
            $this->fail('An over-spend (41 > available 40) must be rejected.');
        } catch (\Throwable $e) {
            $this->assertStringContainsStringIgnoringCase('balance not enough', $e->getMessage(), 'Got: ' . $e->getMessage());
        }

        $this->assertSame('0.0000', (string) DB::table('bd_salaries')->where('id', $row)->value('cut_amount'),
            'A rejected over-spend must apply no cut.');
        $this->assertSame(0, (int) DB::table('users')->where('id', $rcv->id)->value('di'),
            'A rejected over-spend must not credit the receiver.');
        $this->assertSame(0, Charge::where('user_id', $rcv->id)->count(),
            'A rejected over-spend must create no charge row.');
    }

    // ─────────────── (3) sequential double-spend is blocked ───────────────

    /**
     * Balance sufficient for exactly ONE charge. The first drains it; the second
     * (same amount) must be rejected against the now-zero balance. Only one
     * receiver credit and one cut total; balance never negative.
     */
    public function test_bd_second_charge_after_drain_is_blocked(): void
    {
        $bdId = $this->makeAdmin('bd', 0);
        $row  = $this->seedBdSalary($bdId, '40'); // enough for exactly one 40 charge
        $rcv  = $this->makeUser(['di' => 0]);

        $ctrl = new \App\Bd\Controllers\WalletController();
        Auth::login(\App\Models\Admin::find($bdId));

        $sender = Bd::find($bdId);
        $first  = $ctrl->startTransaction($rcv->fresh(), $sender, 40, 4000, 'user');
        $this->assertTrue((bool) $first, 'First charge (40 == available) must succeed.');

        $secondRejected = false;
        try {
            $ctrl->startTransaction($rcv->fresh(), Bd::find($bdId), 40, 4000, 'user');
        } catch (\Throwable $e) {
            $secondRejected = true;
        }
        $this->assertTrue($secondRejected, 'A second charge against a drained balance must be rejected.');

        $cut = (string) DB::table('bd_salaries')->where('id', $row)->value('cut_amount');
        $this->assertSame('40.0000', $cut, 'Total cut must equal one charge, never two (no double-spend).');
        $this->assertTrue(bccomp($cut, '40', 4) <= 0, 'cut_amount must never exceed the salary (balance never negative).');
        $this->assertSame(4000, (int) DB::table('users')->where('id', $rcv->id)->value('di'),
            'Receiver must be credited exactly once.');
    }

    // ─────────────── (4) sub-admin charge: locked sufficiency ───────────────

    /**
     * chargeToSubAdmin on admin_users.di: a first charge drains the super's
     * balance; a second against the drained balance is rejected. The super's di
     * never goes negative and the sub is credited exactly once.
     */
    public function test_super_to_sub_second_charge_after_drain_is_blocked(): void
    {
        $superId = $this->makeAdmin('country', 40);
        $subId   = $this->makeAdmin('sub_country', 0, $superId);

        // super_admin_coins rate = 1 so coinAmount == amount (coin charge type).
        Cache::forget('super_admin_coins');
        DB::table('settings')->updateOrInsert(['key' => 'super_admin_coins'], ['value' => '1', 'updated_at' => now(), 'created_at' => now()]);
        Cache::forever('super_admin_coins', '1');

        Auth::login(\App\Models\Admin::find($superId));
        $ctrl = new \Modules\Country\Http\Controllers\SuperAdmin\WalletController();

        $first = $ctrl->chargeToSubAdmin(['amount' => 40, 'target_id' => $subId, 'charge_type' => 'coin']);
        $this->assertTrue((bool) $first, 'First sub-admin charge (40 == balance) must succeed.');

        $secondRejected = false;
        try {
            $ctrl->chargeToSubAdmin(['amount' => 40, 'target_id' => $subId, 'charge_type' => 'coin']);
        } catch (\Throwable $e) {
            $this->assertStringContainsStringIgnoringCase('insufficient', $e->getMessage(), 'Got: ' . $e->getMessage());
            $secondRejected = true;
        }
        $this->assertTrue($secondRejected, 'A second sub-admin charge against a drained balance must be rejected.');

        $superDi = (int) DB::table('admin_users')->where('id', $superId)->value('di');
        $subDi   = (int) DB::table('admin_users')->where('id', $subId)->value('di');

        $this->assertSame(0, $superDi, 'Super balance must be exactly 0 after one full-balance charge (never negative).');
        $this->assertGreaterThanOrEqual(0, $superDi, 'Super balance must never go negative.');
        $this->assertSame(40, $subDi, 'Sub must be credited exactly once (40), not twice.');
    }

    protected function tearDown(): void
    {
        Cache::forget('super_admin_coins');
        parent::tearDown();
    }
}