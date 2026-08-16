<?php

namespace Tests\Feature\UtdQa;

use App\Helpers\ShippingScopeHelper;
use App\Models\ShippingAdminTransaction;
use App\Models\ShippingAgency;
use App\Models\ShippingSuperAdmin;
use App\Services\ShippingSuperAdminWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Country\Entities\SuperAdmin;

/**
 * SSA - Shipping Super Admin coin engine.
 *
 * Target:
 *   app/Services/ShippingSuperAdminWalletService.php
 *     ::fundFromCountryManager (Country Manager di -> Shipping Super Admin di)
 *     ::chargeAgency          (Shipping Super Admin di -> Agency coins)
 *   app/Helpers/ShippingScopeHelper.php (fail-closed scope resolution)
 *
 * Coins are integers (admin_users.di, agencies.coins). Idempotency is keyed on
 * (operation_uuid, type); the DB carries a UNIQUE(operation_uuid, type) backstop.
 *
 * What we prove:
 *  (1) Idempotency: replaying the SAME operation_uuid applies the move ONCE; the
 *      replay is a no-op (return false) and no extra ledger legs are written.
 *  (2) Non-negative: a debit larger than the sender's balance is rejected and no
 *      balance moves (sender never goes negative, receiver unchanged).
 *  (3) Conservation: on a successful move, sender debit == receiver credit exactly,
 *      and TWO ledger legs (out + in) are written with correct before/after.
 *  (4) Scope fail-closed: ShippingScopeHelper rejects a target/agency in the wrong
 *      country or under the wrong parent -> null (caller must abort).
 *  (5) Overflow guard: a credit that would push the receiver past MAX_COINS is
 *      rejected with no wraparound and no balance change.
 *  (6) Replay stress: N identical replays still apply exactly once (double-spend
 *      style, mirrors SalaryRequestDoubleSpendTest's logic-level proof).
 *
 * Concurrency proof boundary
 * --------------------------
 * lockForUpdate serialising two OS threads on a row can only be shown with two
 * real MySQL connections; a single PHPUnit worker cannot reproduce true
 * parallelism. What is asserted here is the LOGIC the lock enforces: the
 * in-transaction idempotency short-circuit and the in-lock sufficiency check mean
 * a retried/replayed operation can never double-apply. The UNIQUE(operation_uuid,
 * type) index is the DB-level backstop against a genuine race and is documented,
 * not raced, here.
 */
class ShippingSuperAdminWalletServiceTest extends UtdQaTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // may markTestSkipped for non-mysql

        // This layer's schema (admin_users.di + shipping_admin_transactions) is
        // provisioned by 2026_08_10_120000_create_shipping_super_admin_layer.
        // Skip clearly if the test DB has not been migrated for it.
        if (!Schema::hasColumn('admin_users', 'di') || !Schema::hasTable('shipping_admin_transactions')) {
            $this->markTestSkipped(
                'shipping super admin layer not provisioned in meow_qa_test '
                . '(need admin_users.di + shipping_admin_transactions). Run the '
                . '2026_08_10_120000_create_shipping_super_admin_layer migration on the QA schema.'
            );
        }
    }

    private function service(): ShippingSuperAdminWalletService
    {
        return new ShippingSuperAdminWalletService();
    }

    private function makeCountryManager(int $countryId, int $di = 0): SuperAdmin
    {
        $id = DB::table('admin_users')->insertGetId([
            'username'   => 'qa-cm-' . uniqid(),
            'password'   => bcrypt('x'),
            'name'       => 'QA-CM',
            'type'       => 'country',
            'country_id' => $countryId,
            'di'         => $di,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return SuperAdmin::find($id);
    }

    private function makeShippingSuperAdmin(int $countryId, int $parentId, int $di = 0): ShippingSuperAdmin
    {
        $id = DB::table('admin_users')->insertGetId([
            'username'   => 'qa-ssa-' . uniqid(),
            'password'   => bcrypt('x'),
            'name'       => 'QA-SSA',
            'type'       => ShippingSuperAdmin::TYPE,
            'country_id' => $countryId,
            'parent_id'  => $parentId,
            'di'         => $di,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ShippingSuperAdmin::find($id);
    }

    private function makeShippingAgency(int $countryId, int $coins = 0): ShippingAgency
    {
        $agencyId = $this->insertAgency([
            'app_owner_id' => $this->makeUser()->id,
            'type'         => 2,
            'country_id'   => $countryId,
            'coins'        => $coins,
        ]);

        return ShippingAgency::find($agencyId);
    }

    private function di(int $adminId): int
    {
        return (int) DB::table('admin_users')->where('id', $adminId)->value('di');
    }

    private function agencyCoins(int $agencyId): int
    {
        return (int) DB::table('agencies')->where('id', $agencyId)->value('coins');
    }

    // ─────────────────────────── fundFromCountryManager ───────────────────────

    /**
     * (3) A successful fund moves coins conservatively and writes both legs.
     */
    public function test_fund_moves_coins_and_writes_both_legs(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 1000);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 50);

        $applied = $this->service()->fundFromCountryManager($cm, $ssa, 300, 'ssa-fund-ok');

        $this->assertTrue($applied, 'A valid fund must apply.');
        $this->assertSame(700, $this->di($cm->id), 'Sender must be debited by exactly 300.');
        $this->assertSame(350, $this->di($ssa->id), 'Receiver must be credited by exactly 300.');

        $out = ShippingAdminTransaction::where('operation_uuid', 'ssa-fund-ok')->where('type', ShippingAdminTransaction::FUND_OUT)->first();
        $in  = ShippingAdminTransaction::where('operation_uuid', 'ssa-fund-ok')->where('type', ShippingAdminTransaction::FUND_IN)->first();
        $this->assertNotNull($out, 'Out leg must be written.');
        $this->assertNotNull($in, 'In leg must be written.');
        $this->assertSame(1000, (int) $out->before_amount);
        $this->assertSame(700, (int) $out->after_amount);
        $this->assertSame(50, (int) $in->before_amount);
        $this->assertSame(350, (int) $in->after_amount);
        // Conservation: debit == credit.
        $this->assertSame((int) $out->coins, (int) $in->coins);
    }

    /**
     * (1) Replaying the same operation_uuid applies the fund exactly once.
     */
    public function test_fund_is_idempotent_on_operation_uuid(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 1000);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 0);

        $first  = $this->service()->fundFromCountryManager($cm, $ssa, 200, 'ssa-fund-dup');
        $second = $this->service()->fundFromCountryManager($cm, $ssa, 200, 'ssa-fund-dup'); // replay

        $this->assertTrue($first, 'First apply must succeed.');
        $this->assertFalse($second, 'A replay of the same uuid must be a no-op (false).');
        $this->assertSame(800, $this->di($cm->id), 'Balance must reflect a single debit.');
        $this->assertSame(200, $this->di($ssa->id), 'Balance must reflect a single credit.');
        $this->assertSame(
            1,
            ShippingAdminTransaction::where('operation_uuid', 'ssa-fund-dup')->where('type', ShippingAdminTransaction::FUND_OUT)->count(),
            'Exactly one OUT leg for the replayed uuid.'
        );
    }

    /**
     * (2) A fund larger than the sender's balance is rejected; nothing moves.
     */
    public function test_fund_rejects_insufficient_balance_no_negative(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 100);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 0);

        try {
            $this->service()->fundFromCountryManager($cm, $ssa, 101, 'ssa-fund-over'); // 101 > 100
            $this->fail('An over-debit must throw.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame(100, $this->di($cm->id), 'Sender balance must be untouched (never negative).');
        $this->assertSame(0, $this->di($ssa->id), 'Receiver must not be credited on a rejected move.');
        $this->assertSame(0, ShippingAdminTransaction::where('operation_uuid', 'ssa-fund-over')->count(), 'No ledger legs on rejection.');
    }

    /**
     * Edge: the exact-balance fund succeeds and lands the sender on exactly zero
     * (boundary between allowed and rejected).
     */
    public function test_fund_exact_balance_lands_on_zero(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 100);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 0);

        $applied = $this->service()->fundFromCountryManager($cm, $ssa, 100, 'ssa-fund-exact');

        $this->assertTrue($applied);
        $this->assertSame(0, $this->di($cm->id), 'Draining the exact balance is allowed and lands on zero.');
        $this->assertSame(100, $this->di($ssa->id));
    }

    /**
     * (5) A fund that would push the receiver past MAX_COINS is rejected with no
     * wraparound and no balance change.
     */
    public function test_fund_overflow_is_rejected(): void
    {
        $countryId = 1;
        $max = ShippingSuperAdminWalletService::MAX_COINS;
        // Sender can afford it; the block must come from the receiver overflow guard.
        $cm  = $this->makeCountryManager($countryId, $max);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, $max - 5); // headroom only 5

        try {
            $this->service()->fundFromCountryManager($cm, $ssa, 10, 'ssa-fund-of'); // 10 > headroom 5
            $this->fail('An overflowing credit must throw.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame($max, $this->di($cm->id), 'Sender untouched on overflow rejection.');
        $this->assertSame($max - 5, $this->di($ssa->id), 'Receiver untouched (no wraparound) on overflow rejection.');
    }

    /**
     * (6) N identical replays apply exactly once (double-spend logic proof).
     */
    public function test_fund_repeated_replays_apply_once(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 1000);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 0);

        for ($i = 0; $i < 5; $i++) {
            $this->service()->fundFromCountryManager($cm, $ssa, 120, 'ssa-fund-stress');
        }

        $this->assertSame(880, $this->di($cm->id), 'Five replays must debit exactly once.');
        $this->assertSame(120, $this->di($ssa->id), 'Five replays must credit exactly once.');
        $this->assertSame(1, ShippingAdminTransaction::where('operation_uuid', 'ssa-fund-stress')->where('type', ShippingAdminTransaction::FUND_OUT)->count());
    }

    // ───────────────────────────────── chargeAgency ───────────────────────────

    /**
     * (3) A successful charge moves di -> agency coins conservatively, both legs.
     */
    public function test_charge_moves_coins_and_writes_both_legs(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 0);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 500);
        $agency = $this->makeShippingAgency($countryId, 40);

        $applied = $this->service()->chargeAgency($ssa, $agency, 250, 'ssa-charge-ok');

        $this->assertTrue($applied);
        $this->assertSame(250, $this->di($ssa->id), 'Sender di must be debited by 250.');
        $this->assertSame(290, $this->agencyCoins($agency->id), 'Agency coins must be credited by 250.');

        $out = ShippingAdminTransaction::where('operation_uuid', 'ssa-charge-ok')->where('type', ShippingAdminTransaction::CHARGE_OUT)->first();
        $in  = ShippingAdminTransaction::where('operation_uuid', 'ssa-charge-ok')->where('type', ShippingAdminTransaction::CHARGE_IN)->first();
        $this->assertNotNull($out);
        $this->assertNotNull($in);
        $this->assertSame(500, (int) $out->before_amount);
        $this->assertSame(250, (int) $out->after_amount);
        $this->assertSame(40, (int) $in->before_amount);
        $this->assertSame(290, (int) $in->after_amount);
        $this->assertSame((int) $out->coins, (int) $in->coins);
    }

    /**
     * (1) Charge is idempotent on operation_uuid.
     */
    public function test_charge_is_idempotent_on_operation_uuid(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 0);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 500);
        $agency = $this->makeShippingAgency($countryId, 0);

        $first  = $this->service()->chargeAgency($ssa, $agency, 200, 'ssa-charge-dup');
        $second = $this->service()->chargeAgency($ssa, $agency, 200, 'ssa-charge-dup'); // replay

        $this->assertTrue($first);
        $this->assertFalse($second, 'A replay of the same uuid must be a no-op.');
        $this->assertSame(300, $this->di($ssa->id), 'Single debit.');
        $this->assertSame(200, $this->agencyCoins($agency->id), 'Single credit.');
        $this->assertSame(1, ShippingAdminTransaction::where('operation_uuid', 'ssa-charge-dup')->where('type', ShippingAdminTransaction::CHARGE_OUT)->count());
    }

    /**
     * (2) A charge larger than the sender's di is rejected; nothing moves.
     */
    public function test_charge_rejects_insufficient_balance_no_negative(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 0);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 100);
        $agency = $this->makeShippingAgency($countryId, 10);

        try {
            $this->service()->chargeAgency($ssa, $agency, 101, 'ssa-charge-over');
            $this->fail('An over-charge must throw.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame(100, $this->di($ssa->id), 'Sender di untouched (never negative).');
        $this->assertSame(10, $this->agencyCoins($agency->id), 'Agency untouched on rejection.');
        $this->assertSame(0, ShippingAdminTransaction::where('operation_uuid', 'ssa-charge-over')->count());
    }

    /**
     * (6) N identical charge replays apply exactly once.
     */
    public function test_charge_repeated_replays_apply_once(): void
    {
        $countryId = 1;
        $cm  = $this->makeCountryManager($countryId, 0);
        $ssa = $this->makeShippingSuperAdmin($countryId, $cm->id, 1000);
        $agency = $this->makeShippingAgency($countryId, 0);

        for ($i = 0; $i < 5; $i++) {
            $this->service()->chargeAgency($ssa, $agency, 130, 'ssa-charge-stress');
        }

        $this->assertSame(870, $this->di($ssa->id), 'Five replays debit once.');
        $this->assertSame(130, $this->agencyCoins($agency->id), 'Five replays credit once.');
        $this->assertSame(1, ShippingAdminTransaction::where('operation_uuid', 'ssa-charge-stress')->where('type', ShippingAdminTransaction::CHARGE_OUT)->count());
    }

    // ─────────────────────────── ShippingScopeHelper (4) ──────────────────────

    /**
     * (4) fundableShippingSuperAdmin: an in-scope target (own child, same country)
     * resolves; a foreign country, a foreign parent, or a country-less manager
     * fail closed to null.
     */
    public function test_scope_fundable_shipping_super_admin_fail_closed(): void
    {
        $cm = $this->makeCountryManager(1, 0);

        $good     = $this->makeShippingSuperAdmin(1, $cm->id, 0);          // own child, same country
        $foreignC = $this->makeShippingSuperAdmin(2, $cm->id, 0);          // own child, WRONG country
        $foreignP = $this->makeShippingSuperAdmin(1, $cm->id + 9999, 0);   // right country, WRONG parent

        $this->assertNotNull(ShippingScopeHelper::fundableShippingSuperAdmin($cm, $good->id), 'in-scope target must resolve.');
        $this->assertNull(ShippingScopeHelper::fundableShippingSuperAdmin($cm, $foreignC->id), 'wrong-country target must fail closed.');
        $this->assertNull(ShippingScopeHelper::fundableShippingSuperAdmin($cm, $foreignP->id), 'wrong-parent target must fail closed.');
        $this->assertNull(ShippingScopeHelper::fundableShippingSuperAdmin($cm, 999999999), 'missing target must fail closed.');

        // A country manager with no country can fund nobody.
        $noCountryCm = $this->makeCountryManager(0, 0);
        $child = $this->makeShippingSuperAdmin(1, $noCountryCm->id, 0);
        $this->assertNull(
            ShippingScopeHelper::fundableShippingSuperAdmin($noCountryCm, $child->id),
            'a country-less manager must fund nobody.'
        );
    }

    /**
     * (4) chargeableAgency: same-country shipping agency resolves; a foreign
     * country agency, and a country-less super admin, fail closed to null.
     */
    public function test_scope_chargeable_agency_fail_closed(): void
    {
        $cm  = $this->makeCountryManager(1, 0);
        $ssa = $this->makeShippingSuperAdmin(1, $cm->id, 0);

        $good    = $this->makeShippingAgency(1, 0);   // same country
        $foreign = $this->makeShippingAgency(2, 0);   // wrong country

        $this->assertNotNull(ShippingScopeHelper::chargeableAgency($ssa, $good->id), 'same-country agency must resolve.');
        $this->assertNull(ShippingScopeHelper::chargeableAgency($ssa, $foreign->id), 'wrong-country agency must fail closed.');
        $this->assertNull(ShippingScopeHelper::chargeableAgency($ssa, 999999999), 'missing agency must fail closed.');

        $noCountrySsa = $this->makeShippingSuperAdmin(0, $cm->id, 0);
        $this->assertNull(
            ShippingScopeHelper::chargeableAgency($noCountrySsa, $good->id),
            'a country-less shipping super admin must charge nobody.'
        );
    }

    /**
     * (4) A non-shipping agency (type=1) is NOT chargeable even in the same
     * country: the ShippingAgency model's type=2 global scope makes it invisible,
     * so the helper fails closed.
     */
    public function test_scope_rejects_non_shipping_agency(): void
    {
        $cm  = $this->makeCountryManager(1, 0);
        $ssa = $this->makeShippingSuperAdmin(1, $cm->id, 0);

        $normalAgencyId = $this->insertAgency([
            'app_owner_id' => $this->makeUser()->id,
            'type'         => 1, // NOT a shipping agency
            'country_id'   => 1,
        ]);

        $this->assertNull(
            ShippingScopeHelper::chargeableAgency($ssa, $normalAgencyId),
            'a non-shipping (type=1) agency must not be chargeable through the shipping path.'
        );
    }

    /**
     * assertPositive: zero and negative coin amounts are rejected for both flows.
     */
    public function test_non_positive_amount_is_rejected(): void
    {
        $cm  = $this->makeCountryManager(1, 1000);
        $ssa = $this->makeShippingSuperAdmin(1, $cm->id, 1000);
        $agency = $this->makeShippingAgency(1, 0);

        foreach ([0, -5] as $bad) {
            try {
                $this->service()->fundFromCountryManager($cm, $ssa, $bad, 'ssa-fund-bad-' . $bad);
                $this->fail("fund must reject amount {$bad}.");
            } catch (\RuntimeException $e) { /* expected */ }

            try {
                $this->service()->chargeAgency($ssa, $agency, $bad, 'ssa-charge-bad-' . $bad);
                $this->fail("charge must reject amount {$bad}.");
            } catch (\RuntimeException $e) { /* expected */ }
        }

        // Nothing moved.
        $this->assertSame(1000, $this->di($cm->id));
        $this->assertSame(1000, $this->di($ssa->id));
        $this->assertSame(0, $this->agencyCoins($agency->id));
        $this->assertSame(0, ShippingAdminTransaction::count());
    }
}