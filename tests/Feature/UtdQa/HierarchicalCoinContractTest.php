<?php

namespace Tests\Feature\UtdQa;

use App\Models\Bd;
use App\Models\BdSalary;
use App\Models\Charge;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HC - Hierarchical coin contract across the manager roles.
 *
 * Targets:
 *   Modules/Country/Http/Controllers/SuperAdmin/WalletController.php
 *     ::chargeToAgency (guard), ::transfer (dead-path guard)
 *   Modules/Region/Http/Controllers/WalletController.php
 *     ::chargeToAgency (guard), ::transfer (dead-path guard)
 *   app/Bd/Controllers/WalletController.php
 *     ::chargeToAgency (guard), ::transfer (dead-path guard),
 *     ::chargeToUser (scope), ::startTransaction (atomic distribution)
 *
 * Contract proven here:
 *  (1) Directly funding a shipping agency (agencies.type=2) with coins via the
 *      BD / country-manager / area-manager charge path is BLOCKED at the entry
 *      point (chargeToAgency throws), and the agency coin balance is unchanged.
 *      The only sanctioned path is the shipping super admin layer
 *      (ShippingSuperAdminWalletService), covered by ShippingSuperAdminWalletServiceTest.
 *  (2) transfer() on all three portals is a clean dead-path guard: it does NOT
 *      throw a fatal (the referenced App\Models\BDSallary class was removed) and
 *      moves no money.
 *  (3) BD chargeToUser is scoped: a target user whose agency.bd_id != Auth::id()
 *      is rejected (raw-id IDOR closed); an in-scope user is charged.
 *
 * Auth model: these portals resolve the actor from Auth::user() (the id is used
 * to load the role model). We log the actor in on the default guard with the
 * matching admin_users id so Auth::id() is the actor; the guards we exercise run
 * before any guard-specific capability check.
 *
 * DB note: agencies.coins and admin_users.di are integer coin balances. The
 * bd_salaries table (single-l, the BdSalary model) gates the BD user-charge; if
 * that table is not provisioned the BD money-path tests skip with a clear reason.
 */
class HierarchicalCoinContractTest extends UtdQaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // BD user-charge and its distribution are gated by the BdSalary table.
        if (!Schema::hasTable('bd_salaries')) {
            $this->markTestSkipped('bd_salaries table not provisioned in meow_qa_test; run 2025_08_05_114845_create_bd_salaries_table + 2025_08_06_062656.');
        }
    }

    // ─────────────────────────── fixtures ───────────────────────────

    private function makeAdmin(string $type, int $di = 0): int
    {
        return DB::table('admin_users')->insertGetId([
            'username'   => 'qa-' . $type . '-' . uniqid(),
            'password'   => bcrypt('x'),
            'name'       => 'QA-' . $type,
            'type'       => $type,
            'di'         => $di,
            'country_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function shippingAgencyCoins(int $agencyId): int
    {
        return (int) DB::table('agencies')->where('id', $agencyId)->value('coins');
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

    // ──────────────────── (1) chargeToAgency is blocked ────────────────────

    /**
     * BD -> shipping agency direct coin charge is blocked with no coin move.
     */
    public function test_bd_charge_to_shipping_agency_is_blocked_no_money_move(): void
    {
        $bdId = $this->makeAdmin('bd', 100000);
        Auth::login(\App\Models\Admin::find($bdId));

        $agencyId = $this->insertAgency([
            'app_owner_id' => $this->makeUser()->id,
            'type'         => 2,
            'country_id'   => 1,
            'coins'        => 500,
        ]);

        $controller = new \App\Bd\Controllers\WalletController();

        try {
            $controller->chargeToAgency(['amount' => 100, 'target_id' => $agencyId]);
            $this->fail('BD direct charge to a shipping agency must be blocked.');
        } catch (\Throwable $e) {
            $this->assertStringContainsStringIgnoringCase('shipping super admin', $e->getMessage(), 'Block must be the hierarchy guard, not an unrelated fatal. Got: ' . $e->getMessage());
        }

        $this->assertSame(500, $this->shippingAgencyCoins($agencyId), 'Agency coins must be untouched by a blocked BD charge.');
    }

    /**
     * Country manager (superadmin) -> shipping agency direct coin charge is blocked.
     */
    public function test_country_manager_charge_to_shipping_agency_is_blocked_no_money_move(): void
    {
        $cmId = $this->makeAdmin('country', 100000);
        Auth::login(\App\Models\Admin::find($cmId));

        $agencyId = $this->insertAgency([
            'app_owner_id' => $this->makeUser()->id,
            'type'         => 2,
            'country_id'   => 1,
            'coins'        => 700,
        ]);

        $controller = new \Modules\Country\Http\Controllers\SuperAdmin\WalletController();

        try {
            $controller->chargeToAgency(['amount' => 100, 'target_id' => $agencyId, 'charge_type' => 'dollar']);
            $this->fail('Country manager direct charge to a shipping agency must be blocked.');
        } catch (\Throwable $e) {
            $this->assertStringContainsStringIgnoringCase('shipping super admin', $e->getMessage(), 'Got: ' . $e->getMessage());
        }

        $this->assertSame(700, $this->shippingAgencyCoins($agencyId), 'Agency coins must be untouched by a blocked country-manager charge.');
    }

    /**
     * Area manager -> shipping agency direct coin charge is blocked.
     */
    public function test_area_manager_charge_to_shipping_agency_is_blocked_no_money_move(): void
    {
        $amId = $this->makeAdmin('region', 100000);
        Auth::login(\App\Models\Admin::find($amId));

        $agencyId = $this->insertAgency([
            'app_owner_id' => $this->makeUser()->id,
            'type'         => 2,
            'country_id'   => 1,
            'coins'        => 900,
        ]);

        $controller = new \Modules\Region\Http\Controllers\WalletController();

        try {
            $controller->chargeToAgency(['amount' => 100, 'target_id' => $agencyId, 'charge_type' => 'dollar']);
            $this->fail('Area manager direct charge to a shipping agency must be blocked.');
        } catch (\Throwable $e) {
            $this->assertStringContainsStringIgnoringCase('shipping super admin', $e->getMessage(), 'Got: ' . $e->getMessage());
        }

        $this->assertSame(900, $this->shippingAgencyCoins($agencyId), 'Agency coins must be untouched by a blocked area-manager charge.');
    }

    // ──────────────────── (2) transfer() dead-path guard ────────────────────

    /**
     * transfer() on each portal returns cleanly (no fatal from the removed
     * BDSallary class) and moves no money. The methods return a redirect
     * (back()) after the guard, so the assertion is "did not throw".
     */
    public function test_transfer_is_a_clean_dead_path_on_all_portals(): void
    {
        // BD
        $bdId = $this->makeAdmin('bd', 0);
        Auth::login(\App\Models\Admin::find($bdId));
        $req = \Illuminate\Http\Request::create('/bd/salary/transfer', 'POST', ['amount' => 50]);
        app()->instance('request', $req);
        $walletsBefore = DB::table('users_wallets')->count();
        try {
            (new \App\Bd\Controllers\WalletController())->transfer($req);
        } catch (\Throwable $e) {
            $this->fail('BD transfer must not throw a fatal (dead-path guard). Got: ' . $e->getMessage());
        }
        Auth::logout();

        // Country manager
        $cmId = $this->makeAdmin('country', 0);
        Auth::login(\App\Models\Admin::find($cmId));
        $req2 = \Illuminate\Http\Request::create('/superadmin/salary/transfer', 'POST', ['amount' => 50]);
        app()->instance('request', $req2);
        try {
            (new \Modules\Country\Http\Controllers\SuperAdmin\WalletController())->transfer($req2);
        } catch (\Throwable $e) {
            $this->fail('Country manager transfer must not throw a fatal. Got: ' . $e->getMessage());
        }
        Auth::logout();

        // Area manager
        $amId = $this->makeAdmin('region', 0);
        Auth::login(\App\Models\Admin::find($amId));
        $req3 = \Illuminate\Http\Request::create('/areaManager/salary/transfer', 'POST', ['amount' => 50]);
        app()->instance('request', $req3);
        try {
            (new \Modules\Region\Http\Controllers\WalletController())->transfer($req3);
        } catch (\Throwable $e) {
            $this->fail('Area manager transfer must not throw a fatal. Got: ' . $e->getMessage());
        }

        $walletsAfter = DB::table('users_wallets')->count();
        $this->assertSame($walletsBefore, $walletsAfter, 'transfer() dead-path must not create any wallet rows / move money.');
    }

    // ──────────────────── (3) BD chargeToUser scope ────────────────────

    /**
     * BD-A charging a user whose agency belongs to BD-B is rejected (raw-id
     * IDOR closed): the guard fails closed and no coins are credited.
     */
    public function test_bd_charge_to_out_of_scope_user_is_rejected(): void
    {
        $bdA = $this->makeAdmin('bd', 0);
        $bdB = $this->makeAdmin('bd', 0);
        $this->seedBdSalary($bdA, '1000');

        // Agency belongs to BD-B; the target user is in that agency.
        $agencyB = $this->insertAgency([
            'app_owner_id' => $this->makeUser()->id,
            'type'         => 1,
            'country_id'   => 1,
            'bd_id'        => $bdB,
        ]);
        $foreignUser = $this->makeUser(['agency_id' => $agencyB, 'di' => 0]);

        Auth::login(\App\Models\Admin::find($bdA));
        $this->setCoinRate();

        try {
            (new \App\Bd\Controllers\WalletController())->chargeToUser([
                'amount' => 10, 'target_id' => $foreignUser->id,
            ]);
            $this->fail('BD-A must not be able to charge a user outside its scope.');
        } catch (\Throwable $e) {
            // expected — scope guard
        }

        $this->assertSame(0, (int) DB::table('users')->where('id', $foreignUser->id)->value('di'), 'Out-of-scope user must not be credited.');
        $this->assertSame(0, Charge::where('user_id', $foreignUser->id)->count(), 'No charge row for a rejected out-of-scope charge.');
    }

    /**
     * BD-A charging a user in an agency it owns (agency.bd_id = A) succeeds and
     * credits exactly amount * rate coins.
     */
    public function test_bd_charge_to_in_scope_user_succeeds(): void
    {
        $bdA = $this->makeAdmin('bd', 0);
        $this->seedBdSalary($bdA, '1000');

        $agencyA = $this->insertAgency([
            'app_owner_id' => $this->makeUser()->id,
            'type'         => 1,
            'country_id'   => 1,
            'bd_id'        => $bdA,
        ]);
        $inScopeUser = $this->makeUser(['agency_id' => $agencyA, 'di' => 0]);

        Auth::login(\App\Models\Admin::find($bdA));
        $rate = $this->setCoinRate();

        $result = (new \App\Bd\Controllers\WalletController())->chargeToUser([
            'amount' => 10, 'target_id' => $inScopeUser->id,
        ]);

        $this->assertTrue((bool) $result, 'In-scope BD charge must succeed.');
        $this->assertSame(10 * $rate, (int) DB::table('users')->where('id', $inScopeUser->id)->value('di'), 'In-scope user must be credited amount*rate.');
    }

    private function setCoinRate(int $rate = 100): int
    {
        // stopSwitch() fails CLOSED (blocks charge) when the flag is absent, so the
        // in-scope happy path needs the flag explicitly OFF. This is orthogonal to
        // the scope/atomicity logic we are proving.
        foreach (['bd_stop_charge', 'stop_charge'] as $flag) {
            \Cache::forget($flag);
            DB::table('settings')->updateOrInsert(['key' => $flag], ['value' => '0', 'updated_at' => now(), 'created_at' => now()]);
            \Cache::forget($flag);
        }
        \Cache::forget('user_coins');
        DB::table('settings')->updateOrInsert(['key' => 'user_coins'], ['value' => (string) $rate, 'updated_at' => now(), 'created_at' => now()]);
        \Cache::forget('user_coins');
        return $rate;
    }
}
