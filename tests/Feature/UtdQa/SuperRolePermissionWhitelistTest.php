<?php

namespace Tests\Feature\UtdQa;

use App\Admin\Controllers\SuperRoleController;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SRW - Super-role permission whitelist (no privilege escalation to '*').
 *
 * Target:
 *   app/Admin/Controllers/SuperRoleController.php::updatePermissionRole
 *
 * The endpoint that saves the permission checkboxes for the scoped roles
 * (country, region) used to sync() whatever permission IDs the form
 * posted. A crafted request could therefore attach the global wildcard
 * permission ('*', the owner-level grant) to a country/region role and
 * hand it full-platform control.
 *
 * The fix intersects the posted IDs with a server-built whitelist derived from
 * the SAME permissionTypes filter index() uses for the edited role's scope
 * (country -> type 'country', region -> type 'region'), and
 * explicitly drops the '*' permission (where slug != '*'). Anything not on the
 * whitelist is discarded before sync().
 *
 * What we prove:
 *  (1) '*' injection is neutralised: when the wildcard permission id is posted
 *      alongside legitimate same-scope ids, only the legitimate ids end up bound
 *      to the role; '*' never appears in role->permissions.
 *  (2) A foreign-scope permission (belongs to a DIFFERENT permissionType) posted
 *      into the request is also stripped — the whitelist is scope-exact, not
 *      merely a '*' blocklist.
 * Both scoped roles (country and region) are exercised.
 */
class SuperRolePermissionWhitelistTest extends UtdQaTestCase
{
    /** Acting admin with the 'administrator' role so can('*') short-circuits. */
    private function loginAsSuperAdmin(): void
    {
        $adminId = DB::table('admin_users')->insertGetId([
            'username'   => 'qa-srw-actor-' . uniqid(),
            'password'   => bcrypt('x'),
            'name'       => 'QA-SRW',
            'type'       => 'administrator',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $roleId = DB::table('admin_roles')->insertGetId([
            'name' => 'Administrator', 'slug' => 'administrator',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('admin_role_users')->insert([
            'role_id' => $roleId, 'user_id' => $adminId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        \Encore\Admin\Facades\Admin::guard()->login(Admin::find($adminId));
    }

    /** Insert a permission and, optionally, a permission_types row binding it to $type. */
    private function makePermission(string $slug, ?string $type): int
    {
        $pid = DB::table('admin_permissions')->insertGetId([
            'name' => 'QA ' . $slug, 'slug' => $slug,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        if ($type !== null) {
            DB::table('permission_types')->insert([
                'permission_id' => $pid, 'type' => $type,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $pid;
    }

    private function makeRole(string $slug): int
    {
        return DB::table('admin_roles')->insertGetId([
            'name' => 'QA ' . $slug, 'slug' => $slug,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Drive the real endpoint: POST permissions[] + role_id through
     * updatePermissionRole and return the ids actually bound afterwards.
     */
    private function callUpdate(int $roleId, array $permissionIds): array
    {
        $request = Request::create('/admin/super-roles/permissions', 'POST', [
            'role_id'     => $roleId,
            'permissions' => $permissionIds,
        ]);
        app()->instance('request', $request);

        (new SuperRoleController())->updatePermissionRole($request);

        return Role::find($roleId)->permissions()->pluck('admin_permissions.id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * (1)+(2) for the country role: '*' (wildcard) and a foreign-scope
     * permission are both stripped; only the legitimate country-scope ids
     * survive.
     */
    public function test_star_and_foreign_scope_are_stripped_for_super_admin(): void
    {
        $this->loginAsSuperAdmin();

        // '*' carries a country type row too, to prove exclusion is by slug
        // (not merely by "wrong type").
        $starId    = $this->makePermission('*', 'country');
        $legitA    = $this->makePermission('qa-sa-browse-' . uniqid(), 'country');
        $legitB    = $this->makePermission('qa-sa-show-' . uniqid(), 'country');
        $foreignId = $this->makePermission('qa-am-only-' . uniqid(), 'region');

        $roleId = $this->makeRole('country');

        $bound = $this->callUpdate($roleId, [$starId, $legitA, $legitB, $foreignId]);

        $this->assertNotContains($starId, $bound, "The wildcard '*' permission must NEVER be bound to country.");
        $this->assertNotContains($foreignId, $bound, 'A foreign-scope (region) permission must be stripped from country.');
        $this->assertContains($legitA, $bound, 'Legitimate country-scope permission A must be bound.');
        $this->assertContains($legitB, $bound, 'Legitimate country-scope permission B must be bound.');
        $this->assertEqualsCanonicalizing([$legitA, $legitB], $bound, 'Only the two legitimate ids may be bound.');
    }

    /**
     * (1)+(2) for the region role: same guarantees against '*' and a
     * country-scope permission leaking in.
     */
    public function test_star_and_foreign_scope_are_stripped_for_area_manager(): void
    {
        $this->loginAsSuperAdmin();

        $starId    = $this->makePermission('*', 'region');
        $legitA    = $this->makePermission('qa-am-browse-' . uniqid(), 'region');
        $foreignId = $this->makePermission('qa-sa-only-' . uniqid(), 'country');

        $roleId = $this->makeRole('region');

        $bound = $this->callUpdate($roleId, [$starId, $legitA, $foreignId]);

        $this->assertNotContains($starId, $bound, "The wildcard '*' permission must NEVER be bound to region.");
        $this->assertNotContains($foreignId, $bound, 'A country-scope permission must be stripped from region.');
        $this->assertContains($legitA, $bound, 'Legitimate region-scope permission must be bound.');
        $this->assertEqualsCanonicalizing([$legitA], $bound, 'Only the legitimate id may be bound.');
    }
}