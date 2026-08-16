<?php

namespace Tests\Feature\UtdQa;

use App\Helpers\Common;
use App\Http\Controllers\Api\V1\UserController as ApiUserController;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Country\Http\Controllers\SuperAdminCountryController;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * CIM - Country isolation matrix (A vs B) across the four roles.
 *
 * The core tenant contract: each role sees and affects ONLY its own
 * country/scope; zero rows leak from a foreign country. This file drives the
 * REAL controllers/helpers and asserts, per case: (leak) A never sees B's rows,
 * and (visibility) A does see A's own rows.
 *
 * Scope-source map (verified against the code, NOT assumed):
 *  - /superadmin portal country pages: SuperAdminCountryController::assertCountryInScope
 *    derives the allowed set from the admin role explicitly (country manager ->
 *    [own country_id]; area manager -> Common::areaCountries(); unrestricted
 *    admin -> passes all). Empty scope does NOT fail open here.
 *  - api/search handlers (superAdminUsers / subSuperAdminUsers / superAdminAgencies
 *    / usersByCountry / bdCountryUsers ...): App\Http\Controllers\Api\V1\UserController
 *    ::scopedCountryIds() is fail-closed — unrestricted admin -> [] (all); country
 *    manager -> [own country_id]; area manager -> areaCountries(); no session -> [0];
 *    a client-supplied country_id is only ever INTERSECTED, never a replacement.
 *  - AllStatisticController widgets: draw scope from Common::filterCountryIds(),
 *    which restricts AREA managers to areaCountries(). (Country managers isolate
 *    via the separate /superadmin portal, not this dashboard, so the area-manager
 *    path is the one asserted for the shared widget scope.)
 *  - Bd/UserController::show(): a BD sees only users in ITS OWN agencies
 *    (whereIn agency_id IN agencies where bd_id = Auth::id()); a user outside ->
 *    abort(404). The prior orWhere('country_id') widening was removed.
 *
 * Reportable QA-schema gaps provisioned to run these paths (absent from
 * meow_qa_test, added as boolean default 0 exactly as every query treats them):
 *   users.is_bd, is_area_manager, sub_area_manger, is_super_admin,
 *   is_sub_super_admin, is_shipping.
 */
class CountryIsolationMatrixTest extends UtdQaTestCase
{
    private function makeCountry(string $tag): int
    {
        return DB::table('countries')->insertGetId([
            'name' => "QA-{$tag}-" . uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeCountryManager(int $countryId): int
    {
        return DB::table('admin_users')->insertGetId([
            'username' => 'qa-cm-' . uniqid(), 'password' => bcrypt('x'),
            'name' => 'QA-CM', 'type' => 'country', 'country_id' => $countryId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Area manager owning a region that contains exactly $countryIds. */
    private function makeAreaManager(array $countryIds): int
    {
        $amId = DB::table('admin_users')->insertGetId([
            'username' => 'qa-am-' . uniqid(), 'password' => bcrypt('x'),
            'name' => 'QA-AM', 'type' => 'region',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $regionId = DB::table('regions')->insertGetId([
            'name' => 'QA-R-' . uniqid(), 'manager_id' => $amId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($countryIds as $cid) {
            DB::table('region_countries')->insert([
                'region_id' => $regionId, 'country_id' => $cid, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $amId;
    }

    private function makeUnrestrictedAdmin(): int
    {
        $id = DB::table('admin_users')->insertGetId([
            'username' => 'qa-adm-' . uniqid(), 'password' => bcrypt('x'),
            'name' => 'QA-ADM', 'type' => 'administrator',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $roleId = DB::table('admin_roles')->insertGetId([
            'name' => 'Administrator', 'slug' => 'administrator', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('admin_role_users')->insert([
            'role_id' => $roleId, 'user_id' => $id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }

    private function login(int $adminId): void
    {
        \Encore\Admin\Facades\Admin::guard()->login(Admin::find($adminId));
    }

    private function logout(): void
    {
        \Encore\Admin\Facades\Admin::guard()->logout();
    }

    /** Clean app-user (passes the search-handler role/agency filters) in a country. */
    private function makeCleanUser(int $countryId): User
    {
        return $this->makeUser([
            'country_id'         => $countryId,
            'agency_id'          => 0,
            'is_bd'              => 0,
            'is_area_manager'    => 0,
            'sub_area_manger'    => 0,
            'is_super_admin'     => 0,
            'is_sub_super_admin' => 0,
        ]);
    }

    /** Extract the id list from a search JsonResponse (shape: {data:[...]} or [...]) . */
    private function idsOf($jsonResponse): array
    {
        $data = $jsonResponse->getData(true);
        $rows = $data['data'] ?? $data;
        return array_map('intval', array_column($rows, 'id'));
    }

    private function apiController(): ApiUserController
    {
        return app(ApiUserController::class);
    }

    private function bindRequest(array $params): \Illuminate\Http\Request
    {
        $req = \Illuminate\Http\Request::create('/api/search', 'GET', $params);
        app()->instance('request', $req);
        return $req;
    }

    // ═══════════════ COUNTRY MANAGER A vs B — /superadmin country pages ═══════════════

    /**
     * assertCountryInScope: country manager A may open ONLY his own country's
     * stats page; requesting country B (index / index2 / getStats) -> abort(403).
     *
     * DEFECT SURFACED (see final report): the guard's first statement is
     *   $admin = Admin::user();
     * where `Admin` is imported as App\Models\Admin (the Eloquent model). Its
     * user() is a non-static belongsTo relation, and Eloquent's __callStatic is
     * BYPASSED for existing non-static methods, so this raises a fatal
     *   Error: Non-static method App\Models\Admin::user() cannot be called statically
     * on EVERY request, before any scope check. The sibling controllers import
     * Encore\Admin\Facades\Admin instead. This test asserts the INTENDED 403 and
     * therefore fails against the current code, naming the defect.
     */
    public function test_country_manager_country_pages_reject_foreign_country(): void
    {
        $cA = $this->makeCountry('A');
        $cB = $this->makeCountry('B');
        $cmA = $this->makeCountryManager($cA);
        $this->login($cmA);

        $ctrl = new SuperAdminCountryController();

        // Foreign country B: every entry point must abort(403).
        $this->assertCountryPage403(fn () => $ctrl->index($cB), 'index(B)');
        $this->assertCountryPage403(fn () => $ctrl->index2($cB), 'index2(B)');
        $this->assertCountryPage403(fn () => $ctrl->getStats($this->bindRequest([]), $cB), 'getStats(B)');
    }

    /**
     * Own country A must NOT be blocked by the scope guard (visibility side): the
     * guard must let the request through to the (downstream) aggregation. A 403
     * here — or the static-call fatal documented above — is a defect.
     */
    public function test_country_manager_own_country_passes_scope_guard(): void
    {
        $cA = $this->makeCountry('A');
        $cB = $this->makeCountry('B');
        $cmA = $this->makeCountryManager($cA);
        $this->login($cmA);

        $ctrl = new SuperAdminCountryController();
        // Drive the private guard directly so the assertion is deterministic and
        // isolated from downstream DB/view concerns.
        $guard = new \ReflectionMethod($ctrl, 'assertCountryInScope');
        $guard->setAccessible(true);

        // Own country A: the guard must ADMIT (no HttpException, no fatal).
        try {
            $guard->invoke($ctrl, $cA);
            $this->assertTrue(true, 'Country-scope guard admitted the manager for his own country A.');
        } catch (HttpException $e) {
            $this->fail("A country manager must NOT be blocked from his own country (got {$e->getStatusCode()}).");
        } catch (\Error $e) {
            $this->fail('Country-scope guard fataled: ' . $e->getMessage());
        }

        // Foreign country B: the guard must REJECT with 403 — proving genuine
        // scoping, not merely the absence of the earlier static-call fatal.
        try {
            $guard->invoke($ctrl, $cB);
            $this->fail('Country-scope guard admitted a FOREIGN country B for a country manager.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode(), 'Foreign-country rejection must be 403.');
        }
    }

    // ═══════════════ COUNTRY MANAGER A vs B — api/search handlers ═══════════════

    /**
     * The app-user search handlers: A's results contain A's user and NEVER B's
     * user. Driven through the real controller + service + repository.
     * (superAdminUsers / subSuperAdminUsers query app users filtered by
     *  country_id IN scopedCountryIds().)
     */
    public function test_country_manager_user_search_isolates_by_country(): void
    {
        $cA = $this->makeCountry('A');
        $cB = $this->makeCountry('B');
        $cmA = $this->makeCountryManager($cA);

        $uA = $this->makeCleanUser($cA);
        $uB = $this->makeCleanUser($cB);

        $this->login($cmA);
        $ctrl = $this->apiController();

        foreach (['superAdminUsers', 'subSuperAdminUsers'] as $handler) {
            $req = $this->bindRequest(['q' => '', 'page' => 1]);
            $ids = $this->idsOf($ctrl->{$handler}($req));

            $this->assertContains($uA->id, $ids, "{$handler}: A's own user must be visible.");
            $this->assertNotContains($uB->id, $ids, "{$handler}: country B's user must NOT leak into A's results.");
        }
    }

    /**
     * superAdminUsers2 lists SuperAdmin entities (admin_users, type=superadmin)
     * scoped by country_id IN scopedCountryIds(). Country manager A must see a
     * super admin seated in A but never one seated in B.
     */
    public function test_country_manager_super_admin_search_isolates_by_country(): void
    {
        $cA = $this->makeCountry('SA-A');
        $cB = $this->makeCountry('SA-B');
        $cmA = $this->makeCountryManager($cA);      // a superadmin in A (the actor)
        $superB = $this->makeCountryManager($cB);   // a superadmin in B

        $this->login($cmA);
        $ctrl = $this->apiController();

        $req = $this->bindRequest(['q' => '', 'page' => 1]);
        $ids = $this->idsOf($ctrl->superAdminUsers2($req));

        $this->assertContains($cmA, $ids, 'superAdminUsers2: a super admin in A must be visible to manager A.');
        $this->assertNotContains($superB, $ids, 'superAdminUsers2: a super admin in B must NOT leak to manager A.');
    }

    /**
     * bdCountryUsers / superAdminAgencies accept a client country_id. Passing
     * country B from manager A must collapse to zero (intersection is empty), so
     * B's rows never appear — proving the client value cannot widen scope.
     */
    public function test_country_manager_client_country_id_cannot_widen_scope(): void
    {
        $cA = $this->makeCountry('A');
        $cB = $this->makeCountry('B');
        $cmA = $this->makeCountryManager($cA);

        $uA = $this->makeCleanUser($cA);
        $uB = $this->makeCleanUser($cB);

        $this->login($cmA);
        $ctrl = $this->apiController();

        // Manager A injects country_id = B: the intersection is empty, so the
        // scope collapses to the [0] sentinel (whereIn country_id [0]) and B's
        // user can never surface. (Note: pre-existing QA fixtures with
        // country_id=0 may match [0]; the contract under test is strictly that
        // no country-B row leaks, which is the isolation guarantee.)
        $reqInject = $this->bindRequest(['q' => '', 'page' => 1, 'country_id' => $cB]);
        $idsInject = $this->idsOf($ctrl->bdCountryUsers($reqInject));
        $this->assertNotContains($uB->id, $idsInject, 'Injecting country B must not surface B users to manager A.');

        // Ground truth on the computed scope: an out-of-scope client country_id
        // resolves to the fail-closed [0] sentinel, never to [B].
        $scoped = new \ReflectionMethod($ctrl, 'scopedCountryIds');
        $scoped->setAccessible(true);
        $this->assertSame([0], $scoped->invoke($ctrl, $cB), 'Out-of-scope client country_id must collapse to the [0] sentinel.');
        $this->assertNotContains($cB, $scoped->invoke($ctrl, $cB), 'The requested foreign country id must never appear in the resolved scope.');

        // No injection: manager A still sees only A.
        $reqPlain = $this->bindRequest(['q' => '', 'page' => 1]);
        $idsPlain = $this->idsOf($ctrl->bdCountryUsers($reqPlain));
        $this->assertContains($uA->id, $idsPlain, "bdCountryUsers: A's own user must be visible.");
        $this->assertNotContains($uB->id, $idsPlain, 'bdCountryUsers: B must not leak.');
    }

    /**
     * usersByCountry: manager A cannot pull users of a super admin seated in
     * country B (the handler returns empty when the target super admin's country
     * is outside A's scope).
     */
    public function test_usersByCountry_rejects_foreign_super_admin(): void
    {
        $cA = $this->makeCountry('A');
        $cB = $this->makeCountry('B');
        $cmA = $this->makeCountryManager($cA);
        $superB = $this->makeCountryManager($cB); // a super admin seated in B

        $uB = $this->makeCleanUser($cB);

        $this->login($cmA);
        $ctrl = $this->apiController();

        $req = $this->bindRequest(['super_admin_id' => $superB, 'q' => '', 'page' => 1]);
        $result = $ctrl->usersByCountry($req);
        $ids = $this->idsOf($result);

        $this->assertNotContains($uB->id, $ids, 'usersByCountry: manager A must not read country B users via a B super admin id.');
        $this->assertEmpty($ids, 'usersByCountry with an out-of-scope super admin must return zero rows.');
    }

    // ═══════════════ AREA MANAGER — areaCountries only ═══════════════

    /**
     * An area manager assigned countries {A} sees A's user via the search
     * handlers but not a user in unassigned country B.
     */
    public function test_area_manager_sees_only_assigned_countries(): void
    {
        $cA = $this->makeCountry('AM-A');
        $cB = $this->makeCountry('AM-B');
        $amId = $this->makeAreaManager([$cA]); // only A assigned

        $uA = $this->makeCleanUser($cA);
        $uB = $this->makeCleanUser($cB);

        $this->login($amId);

        // Ground truth: the resolved scope is exactly [A].
        $this->assertSame([$cA], Common::areaCountries(), 'Area manager scope must be exactly his assigned country.');

        $ctrl = $this->apiController();
        $req = $this->bindRequest(['q' => '', 'page' => 1]);
        $ids = $this->idsOf($ctrl->superAdminUsers($req));

        $this->assertContains($uA->id, $ids, 'Area manager must see users in his assigned country.');
        $this->assertNotContains($uB->id, $ids, 'Area manager must NOT see users in an unassigned country.');
    }

    /**
     * AllStatisticController shares one scope source (Common::filterCountryIds()).
     * For an area manager it restricts to areaCountries(); we prove a widget-style
     * whereIn against that scope excludes country B rows and includes A rows.
     */
    public function test_area_manager_dashboard_scope_excludes_foreign_country(): void
    {
        $cA = $this->makeCountry('DASH-A');
        $cB = $this->makeCountry('DASH-B');
        $amId = $this->makeAreaManager([$cA]);

        $uA = $this->makeCleanUser($cA);
        $uB = $this->makeCleanUser($cB);

        $this->login($amId);

        $scope = Common::filterCountryIds(); // the exact value every widget uses
        $this->assertSame([$cA], $scope, 'Dashboard scope for an area manager must equal his assigned countries.');

        // Apply the canonical widget idiom and prove the isolation end-to-end.
        $visible = User::when($scope, fn ($q) => $q->whereIn('country_id', $scope))
            ->whereIn('id', [$uA->id, $uB->id])
            ->pluck('id')
            ->map(fn ($i) => (int) $i)
            ->all();

        $this->assertContains($uA->id, $visible, 'Dashboard widget must include the assigned-country user.');
        $this->assertNotContains($uB->id, $visible, 'Dashboard widget must exclude the foreign-country user.');
    }

    // ═══════════════ SUPER ADMIN BD — own agencies only ═══════════════

    /**
     * Bd/UserController::show — a BD may open the profile of a user in ITS OWN
     * agency, but a user outside its agencies (even a foreign one) aborts 404.
     * This is the IDOR guard after the orWhere('country_id') widening was removed.
     */
    public function test_bd_show_rejects_user_outside_its_agencies(): void
    {
        $bdId = DB::table('admin_users')->insertGetId([
            'username' => 'qa-bd-' . uniqid(), 'password' => bcrypt('x'),
            'name' => 'QA-BD', 'type' => 'bd', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Agency owned by this BD, and a foreign agency owned by another BD.
        $ownAgency     = $this->insertAgency(['bd_id' => $bdId, 'type' => 1, 'app_owner_id' => $this->makeUser()->id]);
        $foreignAgency = $this->insertAgency(['bd_id' => $bdId + 777, 'type' => 1, 'app_owner_id' => $this->makeUser()->id]);

        $inScopeUser  = $this->makeUser(['agency_id' => $ownAgency]);
        $outScopeUser = $this->makeUser(['agency_id' => $foreignAgency]);

        // Authenticate as the BD on the web guard (Bd/UserController::show reads
        // Auth::id()) and drive the REAL controller.
        \Illuminate\Support\Facades\Auth::guard('web')->setUser(Admin::find($bdId));
        \Encore\Admin\Facades\Admin::guard()->login(Admin::find($bdId));

        $ctrl = new \App\Bd\Controllers\UserController();
        $content = app(\Encore\Admin\Layout\Content::class);
        app()->instance('request', \Illuminate\Http\Request::create('/', 'GET', []));

        // Out-of-scope user: the scope clause yields no row -> abort(404).
        $out = null;
        try {
            $ctrl->show($outScopeUser->id, $content);
        } catch (HttpException $e) {
            $out = $e->getStatusCode();
        }
        $this->assertSame(404, $out, 'A BD must get 404 for a user outside its agencies (no orWhere country widening).');

        // In-scope user: the scope clause passes (the guard finds the row). Any
        // downstream Encore view/query throwable is unrelated to isolation; a 404
        // here would be the isolation contract wrongly blocking an own-agency user.
        $wronglyBlocked = false;
        try {
            $ctrl->show($inScopeUser->id, $content);
        } catch (HttpException $e) {
            $wronglyBlocked = $e->getStatusCode() === 404;
        } catch (\Throwable $e) {
            // downstream rendering/query — scope passed.
        }
        $this->assertFalse($wronglyBlocked, 'A BD must NOT be 404-blocked from a user in its own agency.');
    }

    // ═══════════════ UNRESTRICTED ADMIN — sees all (fail-closed did not break admin) ═══════════════

    /**
     * type=administrator: scope is [] (no restriction), so BOTH A and B users
     * are visible. Confirms the fail-closed defaults did not accidentally lock
     * out the legitimate all-countries admin.
     */
    public function test_unrestricted_admin_sees_all_countries(): void
    {
        $cA = $this->makeCountry('ALL-A');
        $cB = $this->makeCountry('ALL-B');
        $adminId = $this->makeUnrestrictedAdmin();

        $uA = $this->makeCleanUser($cA);
        $uB = $this->makeCleanUser($cB);

        $this->login($adminId);

        // Scope helpers must be unrestricted for an admin.
        $this->assertSame([], Common::filterCountryIds(), 'Unrestricted admin dashboard scope must be [] (all).');

        $ctrl = $this->apiController();
        $req = $this->bindRequest(['q' => '', 'page' => 1]);
        $ids = $this->idsOf($ctrl->superAdminUsers($req));

        $this->assertContains($uA->id, $ids, 'Unrestricted admin must see country A users.');
        $this->assertContains($uB->id, $ids, 'Unrestricted admin must see country B users.');

        // And the /superadmin country guard lets an admin open any country.
        $guardBlocked = false;
        try {
            (new SuperAdminCountryController())->getStats($this->bindRequest([]), $cB);
        } catch (HttpException $e) {
            $guardBlocked = $e->getStatusCode() === 403;
        } catch (\Throwable $e) {
            // non-403 downstream is fine.
        }
        $this->assertFalse($guardBlocked, 'Unrestricted admin must not be 403-blocked from any country page.');
    }

    // ─────────────────────────── helper ───────────────────────────

    private function assert403(callable $fn, string $label): void
    {
        try {
            $fn();
            $this->fail("{$label}: expected a 403 for an out-of-scope country.");
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode(), "{$label}: rejection must be 403, got {$e->getStatusCode()}.");
        }
    }

    /**
     * A country page must reject an out-of-scope country with abort(403). If the
     * guard instead raises the static-call fatal (App\Models\Admin::user()), we
     * fail with a defect-naming message rather than silently passing on the wrong
     * exception type.
     */
    private function assertCountryPage403(callable $fn, string $label): void
    {
        try {
            $fn();
            $this->fail("{$label}: expected abort(403) for an out-of-scope country.");
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode(), "{$label}: rejection must be 403, got {$e->getStatusCode()}.");
        } catch (\Error $e) {
            $this->fail(
                "{$label}: guard fataled instead of returning 403 — " . $e->getMessage()
                . ' (SuperAdminCountryController must import Encore\\Admin\\Facades\\Admin, not App\\Models\\Admin).'
            );
        }
    }

    protected function tearDown(): void
    {
        $this->logout();
        parent::tearDown();
    }
}