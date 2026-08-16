<?php

namespace Tests\Feature\UtdQa;

use App\Http\Middleware\SetCountry;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;

/**
 * S2 - SetCountry middleware writes a scope key to the session ONLY after the
 * client-supplied value is confirmed inside the authenticated admin's scope.
 *
 * Target:
 *   app/Http/Middleware/SetCountry.php::handle()
 *
 * Threat: a scoped manager injects ?filter_country_id / ?area_manager_country_id
 * / ?area_manager_id to widen his scope. The fix gates each key:
 *   - filter_country_id / area_manager_country_id via Common::isCountryInAdminScope()
 *   - area_manager_id  via Common::canPreviewAreaManager() (super only)
 * An out-of-scope value is NOT persisted (the key is forgotten), so a downstream
 * read can never inherit foreign scope.
 *
 * What we prove:
 *  (1) scoped manager injecting an OUT-of-scope filter_country_id       -> not written
 *  (2) scoped manager injecting an IN-scope filter_country_id           -> written
 *  (3) scoped manager injecting area_manager_country_id out-of-scope    -> not written
 *  (4) scoped manager injecting area_manager_id (preview)               -> not written
 *  (5) super admin injecting filter_country_id / area_manager_id        -> written
 *  (6) explicit clear flags always forget the key
 */
class SetCountryMiddlewareGuardTest extends UtdQaTestCase
{
    /** @return array{amId:int,inCountry:int,outCountry:int} */
    private function seedScopedManager(): array
    {
        $amId = DB::table('admin_users')->insertGetId([
            'username' => 'qa-mw-' . uniqid(), 'password' => bcrypt('x'),
            'name' => 'QA-MW', 'type' => 'region',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $inCountry  = DB::table('countries')->insertGetId(['name' => 'QA-IN-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $outCountry = DB::table('countries')->insertGetId(['name' => 'QA-OUT-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);

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

    /** Push $params through the real middleware and return the resulting session. */
    private function runMiddleware(array $params): void
    {
        $request = \Illuminate\Http\Request::create('/admin/anything', 'GET', $params);
        app()->instance('request', $request);

        (new SetCountry())->handle($request, fn ($r) => new \Illuminate\Http\Response('ok'));
    }

    /**
     * (1) Out-of-scope filter_country_id from a scoped manager is not persisted.
     */
    public function test_scoped_manager_out_of_scope_filter_not_written(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);

        $this->runMiddleware(['filter_country_id' => $ctx['outCountry']]);

        $this->assertNull(
            session('filter_country_id'),
            'An out-of-scope filter_country_id must NOT be written to the session.'
        );
    }

    /**
     * (2) In-scope filter_country_id from a scoped manager IS persisted.
     */
    public function test_scoped_manager_in_scope_filter_is_written(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);

        $this->runMiddleware(['filter_country_id' => $ctx['inCountry']]);

        $this->assertSame((string) $ctx['inCountry'], (string) session('filter_country_id'));
    }

    /**
     * (3) Out-of-scope area_manager_country_id from a scoped manager is not persisted.
     */
    public function test_scoped_manager_out_of_scope_area_country_not_written(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);

        $this->runMiddleware(['area_manager_country_id' => $ctx['outCountry']]);

        $this->assertNull(session('area_manager_country_id'));
    }

    /**
     * (4) A scoped manager can never set area_manager_id (preview is super-only).
     * Injecting a foreign manager id must not be persisted, so identity cannot be
     * widened downstream.
     */
    public function test_scoped_manager_cannot_inject_area_manager_id(): void
    {
        $ctx = $this->seedScopedManager();
        $foreignManagerId = $ctx['amId'] + 5000;
        $this->login($ctx['amId']);

        $this->runMiddleware(['area_manager_id' => $foreignManagerId]);

        $this->assertNull(
            session('area_manager_id'),
            'A scoped manager must not be able to write area_manager_id (preview is super-only).'
        );
    }

    /**
     * (5) A super admin may legitimately set both keys.
     */
    public function test_super_admin_may_set_filter_and_preview(): void
    {
        $superId = $this->seedSuperAdmin();
        $anyCountry = DB::table('countries')->insertGetId(['name' => 'QA-ANY-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $someManagerId = 424242;
        $this->login($superId);

        $this->runMiddleware([
            'filter_country_id' => $anyCountry,
            'area_manager_id'   => $someManagerId,
        ]);

        $this->assertSame((string) $anyCountry, (string) session('filter_country_id'), 'super admin filter must be written.');
        $this->assertSame((string) $someManagerId, (string) session('area_manager_id'), 'super admin preview id must be written.');
    }

    /**
     * (6) The explicit clear flags always forget a previously-set key, regardless
     * of role.
     */
    public function test_clear_flags_forget_keys(): void
    {
        $ctx = $this->seedScopedManager();
        $this->login($ctx['amId']);

        session(['filter_country_id' => $ctx['inCountry']]);
        $this->runMiddleware(['clear_country' => 1]);
        $this->assertNull(session('filter_country_id'), 'clear_country must forget filter_country_id.');

        session(['area_manager_country_id' => $ctx['inCountry']]);
        $this->runMiddleware(['clear_area_manager_country' => 1]);
        $this->assertNull(session('area_manager_country_id'), 'clear_area_manager_country must forget the key.');
    }
}