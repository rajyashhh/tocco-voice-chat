<?php

namespace Tests\Feature\UtdQa;

use App\Helpers\Common;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Central country-scope filter is fail-closed.
 *
 * Target:
 *   app/Helpers/Common.php::filterCountryIds()
 *   app/Helpers/Common.php::isCountryInAdminScope()
 *   app/Helpers/Common.php::resolveAreaManagerId()
 *   app/Helpers/Common.php::canPreviewAreaManager()
 *
 * These replace the legacy inline idiom
 *   empty(session('filter_country_id')) ? areaCountries() : (array) session('filter_country_id')
 * which let any client-supplied filter REPLACE the whole scope (fail-open).
 *
 * Contract proven here (return shape is tuned for the
 * ->when($ids, fn($q) => $q->whereIn('country_id', $ids)) idiom, where [] == "all"):
 *   (1) scoped manager + out-of-scope filter  -> [0]  (zero rows, NOT [] fail-open)
 *   (2) scoped manager + in-scope filter       -> [that country]  (intersection)
 *   (3) scoped manager, no filter              -> his countries
 *   (4) super admin, no filter                 -> []  (unrestricted)
 *   (5) super admin, explicit filter           -> that filter
 *   (6) whereIn('country_id', [0]) really is zero rows (the sentinel works)
 *
 * Identity comes from the encore-admin 'admin' guard (Admin::user()); a scoped
 * manager is an admin_users row with type='region' and NO administrator
 * role, so isAdministrator()/can('*') are false and the fail-closed branch runs.
 * A country admin carries the 'administrator' role.
 */
class CountryScopeFilterGuardTest extends UtdQaTestCase
{
    /** @return array{amId:int,inCountry:int,outCountry:int} */
    private function seedScopedManager(): array
    {
        $amId = DB::table('admin_users')->insertGetId([
            'username' => 'qa-sm-' . uniqid(), 'password' => bcrypt('x'),
            'name' => 'QA-SM', 'type' => 'region',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $inCountry  = DB::table('countries')->insertGetId(['name' => 'QA-IN-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $outCountry = DB::table('countries')->insertGetId(['name' => 'QA-OUT-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);

        // Region owned by the manager containing ONLY $inCountry.
        $regionId = DB::table('regions')->insertGetId(['name' => 'QA-R', 'manager_id' => $amId, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('region_countries')->insert(['region_id' => $regionId, 'country_id' => $inCountry, 'created_at' => now(), 'updated_at' => now()]);

        return ['amId' => $amId, 'inCountry' => $inCountry, 'outCountry' => $outCountry];
    }

    private function seedSuperAdmin(): int
    {
        $id = DB::table('admin_users')->insertGetId([
            'username' => 'qa-super-' . uniqid(), 'password' => bcrypt('x'),
            'name' => 'QA-SUPER', 'type' => 'administrator',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $roleId = DB::table('admin_roles')->insertGetId([
            'name' => 'Administrator', 'slug' => 'administrator',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('admin_role_users')->insert([
            'role_id' => $roleId, 'user_id' => $id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function login(int $adminId): void
    {
        \Encore\Admin\Facades\Admin::guard()->login(Admin::find($adminId));
    }

    /**
     * (1) A scoped manager asking for a country OUTSIDE his set gets the zero-row
     * sentinel [0], never [] (which whereIn would treat as "no restriction").
     */
    public function test_scoped_manager_out_of_scope_filter_returns_zero_sentinel(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);
        session(['filter_country_id' => $ctx['outCountry']]);

        $this->assertSame(
            [0],
            Common::filterCountryIds(),
            'Out-of-scope filter must fail CLOSED with [0] (zero rows), not [] (fail-open all-rows).'
        );
    }

    /**
     * (2) A scoped manager asking for a country INSIDE his set gets exactly that
     * country (the intersection), not the whole scope and not [0].
     */
    public function test_scoped_manager_in_scope_filter_returns_intersection(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);
        session(['filter_country_id' => $ctx['inCountry']]);

        $this->assertSame([$ctx['inCountry']], Common::filterCountryIds());
    }

    /**
     * (3) A scoped manager with NO filter is restricted to his own countries.
     */
    public function test_scoped_manager_without_filter_returns_own_countries(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);

        $this->assertSame([$ctx['inCountry']], Common::filterCountryIds());
    }

    /**
     * (4) A super admin with no filter is unrestricted -> [] (all countries).
     */
    public function test_super_admin_without_filter_is_unrestricted(): void
    {
        $superId = $this->seedSuperAdmin();
        $this->login($superId);

        $this->assertSame([], Common::filterCountryIds(), 'Super admin without a filter must be unrestricted ([]).');
    }

    /**
     * (5) A super admin WITH an explicit filter gets exactly that filter applied
     * (they may legitimately target any country).
     */
    public function test_super_admin_with_filter_applies_that_filter(): void
    {
        $superId = $this->seedSuperAdmin();
        $someCountry = DB::table('countries')->insertGetId(['name' => 'QA-ANY-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $this->login($superId);
        session(['filter_country_id' => $someCountry]);

        $this->assertSame([$someCountry], Common::filterCountryIds());
    }

    /**
     * isCountryInAdminScope: scoped manager -> only in-scope true; super -> any true.
     */
    public function test_is_country_in_admin_scope_matrix(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);

        $this->assertTrue(Common::isCountryInAdminScope($ctx['inCountry']), 'in-scope country must be allowed.');
        $this->assertFalse(Common::isCountryInAdminScope($ctx['outCountry']), 'out-of-scope country must be rejected.');

        \Encore\Admin\Facades\Admin::guard()->logout();

        $superId = $this->seedSuperAdmin();
        $this->login($superId);
        $this->assertTrue(Common::isCountryInAdminScope($ctx['outCountry']), 'super admin may target any country.');
    }

    /**
     * resolveAreaManagerId pins a real manager to his own id (no preview widening);
     * canPreviewAreaManager is a super-admin-only capability.
     */
    public function test_identity_pinning_and_preview_capability(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);

        $this->assertSame($ctx['amId'], Common::resolveAreaManagerId(), 'A scoped manager must resolve to his own id.');
        $this->assertFalse(Common::canPreviewAreaManager(), 'A scoped manager must not be able to preview another manager.');

        \Encore\Admin\Facades\Admin::guard()->logout();

        $superId = $this->seedSuperAdmin();
        $this->login($superId);
        $this->assertTrue(Common::canPreviewAreaManager(), 'A super admin may preview a manager.');
    }

    /**
     * (6) Ground-truth edge: whereIn('country_id', [0]) really returns zero rows.
     * This confirms the [0] sentinel from (1) actually hides every real row (real
     * country ids are >= 1), i.e. the fail-closed contract holds end-to-end.
     */
    public function test_zero_sentinel_matches_no_rows(): void
    {
        $ctx = $this->seedScopedManager();
        $owner = $this->makeUser();
        // A real agency in the in-scope country: it must NOT be visible under [0].
        $this->insertAgency([
            'app_owner_id' => $owner->id,
            'country_id'   => $ctx['inCountry'],
            'type'         => 1,
        ]);

        $countUnderSentinel = DB::table('agencies')->whereIn('country_id', [0])->count();
        $this->assertSame(0, $countUnderSentinel, 'whereIn(country_id, [0]) must select zero rows.');

        // Sanity: the same row IS visible when its real country id is used.
        $countInScope = DB::table('agencies')->whereIn('country_id', [$ctx['inCountry']])->count();
        $this->assertGreaterThanOrEqual(1, $countInScope, 'the seeded agency must be visible under its real country id.');
    }
}
