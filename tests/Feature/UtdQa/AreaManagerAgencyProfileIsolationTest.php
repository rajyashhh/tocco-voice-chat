<?php

namespace Tests\Feature\UtdQa;

use App\Models\Admin;
use App\Models\User;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use Modules\Region\Http\Controllers\AgencyController;

/**
 * H2 - Area-Manager portal: an agency profile is scoped to the manager's own
 * countries.
 *
 * Target:
 *   Modules/Region/Http/Controllers/AgencyController.php::profile
 *   The fix scopes the lookup with ->whereIn('country_id', Common::areaCountries())
 *   (for both Agency and the ShippingAgency fallback). Common::areaCountries()
 *   resolves the manager's countries via regions.manager_id -> region_countries
 *   -> countries. An agency in a country outside that set resolves to null and
 *   the method throws "Agency not found" before any tenant data is read.
 *
 * What we prove:
 *  (A) Manager opening an agency whose country is inside his region set ->
 *      succeeds (returns Encore Content).
 *  (B) Manager opening an agency in a country OUTSIDE his set -> rejected:
 *      throws "Agency not found", no data leaked.
 *
 * Auth model: the portal runs on the encore-admin 'admin' guard and the
 * controller gates on Admin::user()->can('*'). The manager admin is given the
 * 'administrator' role so we pass the permission gate and reach the actual
 * country-isolation logic (that gate is orthogonal to the tenant scoping we're
 * proving). Country resolution reads session('area_manager_id').
 */
class AreaManagerAgencyProfileIsolationTest extends UtdQaTestCase
{
    /** @return array{amId:int,inCountry:int,outCountry:int} */
    private function seedManagerWithCountries(): array
    {
        $amId = DB::table('admin_users')->insertGetId([
            'username' => 'qa-am-' . uniqid(), 'password' => bcrypt('x'),
            'name' => 'QA-AM', 'type' => 'region',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // administrator role -> can('*') == true, bypasses the permission gate.
        $roleId = DB::table('admin_roles')->insertGetId([
            'name' => 'Administrator', 'slug' => 'administrator',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('admin_role_users')->insert([
            'role_id' => $roleId, 'user_id' => $amId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $inCountry  = DB::table('countries')->insertGetId(['name' => 'QA-IN-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $outCountry = DB::table('countries')->insertGetId(['name' => 'QA-OUT-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);

        // Region owned by the manager, containing only $inCountry.
        $regionId = DB::table('regions')->insertGetId(['name' => 'QA-R', 'manager_id' => $amId, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('region_countries')->insert(['region_id' => $regionId, 'country_id' => $inCountry, 'created_at' => now(), 'updated_at' => now()]);

        return ['amId' => $amId, 'inCountry' => $inCountry, 'outCountry' => $outCountry];
    }

    private function loginManager(int $amId): void
    {
        session(['area_manager_id' => $amId]);
        \Encore\Admin\Facades\Admin::guard()->login(Admin::find($amId));
    }

    private function profile(int $agencyId, string $tab = 'members'): Content
    {
        $request = \Illuminate\Http\Request::create('/areaManager/profile-agency/' . $agencyId, 'GET', ['tab' => $tab]);
        app()->instance('request', $request);

        return app(AgencyController::class)->profile($agencyId, $request, new Content());
    }

    public function test_manager_can_open_agency_inside_his_countries(): void
    {
        $ctx = $this->seedManagerWithCountries();
        $owner = $this->makeUser();
        $agencyId = $this->insertAgency([
            'app_owner_id' => $owner->id,
            'country_id'   => $ctx['inCountry'],
            'bd_id'        => 0,
            'type'         => 1,
        ]);

        $this->loginManager($ctx['amId']);

        $result = $this->profile($agencyId);

        $this->assertInstanceOf(
            Content::class,
            $result,
            'An area manager must be able to open an agency inside one of his countries.'
        );
    }

    public function test_manager_cannot_open_agency_outside_his_countries(): void
    {
        $ctx = $this->seedManagerWithCountries();
        $owner = $this->makeUser();
        $agencyId = $this->insertAgency([
            'app_owner_id' => $owner->id,
            'country_id'   => $ctx['outCountry'], // outside the manager's region set
            'bd_id'        => 0,
            'type'         => 1,
        ]);

        $this->loginManager($ctx['amId']);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Agency not found');

        $this->profile($agencyId);
    }

    /**
     * Negative case also holds for the ShippingAgency (type=2) fallback branch.
     */
    public function test_manager_cannot_open_foreign_shipping_agency(): void
    {
        $ctx = $this->seedManagerWithCountries();
        $owner = $this->makeUser();
        $agencyId = $this->insertAgency([
            'app_owner_id' => $owner->id,
            'country_id'   => $ctx['outCountry'],
            'bd_id'        => 0,
            'type'         => 2,
        ]);

        $this->loginManager($ctx['amId']);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Agency not found');

        $this->profile($agencyId, 'charges');
    }
}