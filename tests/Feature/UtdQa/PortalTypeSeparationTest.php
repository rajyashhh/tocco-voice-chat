<?php

namespace Tests\Feature\UtdQa;

use App\Http\Middleware\EnsureAdminPortalType;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * PT - Portal separation via the portal.type middleware.
 *
 * Target:
 *   app/Http/Middleware/EnsureAdminPortalType.php::handle()
 *
 * The admin.auth middleware only proves an authenticated admin_users session;
 * before this guard, ANY valid admin session crossed into EVERY portal. The
 * fix enforces, on every request, that Admin::user()->type is in the portal's
 * allow-list, else abort(403). The allow-lists asserted here are the EXACT
 * strings mounted in the route groups:
 *   - bd portal            -> portal.type:bd
 *   - superadmin portal    -> portal.type:country,sub_country
 *   - areaManager portal   -> portal.type:region,sub_region
 *   - shippingAdmin portal -> portal.type:shipping_super_admin
 * (app/Bd/routes.php:56, Modules/Country/Routes/web.php:39, 184,
 *  Modules/Region/Routes/web.php:105, app/ShippingAdmin/routes.php:42)
 *
 * What we prove:
 *  (1) A bd session is REJECTED (403) at every other portal.
 *  (2) Each role reaches its OWN portal (middleware passes the request through).
 *  (3) An unauthenticated request is rejected (403) at every portal.
 *  (4) A closely-named but wrong type (e.g. administrator, sub_super_admin at
 *      the bd portal) is rejected — exact-match, no substring/loose compare.
 */
class PortalTypeSeparationTest extends UtdQaTestCase
{
    /** The four portals and their exact allow-lists. */
    private const PORTALS = [
        'bd'            => ['bd'],
        'superadmin'    => ['country', 'sub_country'],
        'areaManager'   => ['region', 'sub_region'],
        'shippingAdmin' => ['shipping_super_admin'],
    ];

    private function makeAdmin(string $type): int
    {
        return DB::table('admin_users')->insertGetId([
            'username'   => 'qa-pt-' . $type . '-' . uniqid(),
            'password'   => bcrypt('x'),
            'name'       => 'QA-PT',
            'type'       => $type,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function login(int $adminId): void
    {
        \Encore\Admin\Facades\Admin::guard()->login(Admin::find($adminId));
    }

    /**
     * Push a request through the real portal.type guard for $allowedTypes.
     * Returns true if the request passed, false if the guard aborted 403.
     */
    private function passesPortal(array $allowedTypes): bool
    {
        $request = \Illuminate\Http\Request::create('/some/portal/page', 'GET');
        app()->instance('request', $request);

        try {
            (new EnsureAdminPortalType())->handle(
                $request,
                fn ($r) => new \Illuminate\Http\Response('ok'),
                ...$allowedTypes
            );
            return true;
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode(), 'Portal rejection must be a 403, got ' . $e->getStatusCode());
            return false;
        }
    }

    /**
     * (1) A bd session must be REJECTED at superadmin, areaManager and
     * shippingAdmin. Cross-portal access is closed.
     */
    public function test_bd_is_rejected_at_every_other_portal(): void
    {
        $bdId = $this->makeAdmin('bd');
        $this->login($bdId);

        foreach (['superadmin', 'areaManager', 'shippingAdmin'] as $portal) {
            $this->assertFalse(
                $this->passesPortal(self::PORTALS[$portal]),
                "A bd session must be rejected at the {$portal} portal."
            );
        }
    }

    /**
     * (1b) The inverse for each role: none of the non-owning roles may enter a
     * portal they do not own. Full cross matrix.
     */
    public function test_no_role_crosses_into_a_foreign_portal(): void
    {
        // canonical owning type per portal (first entry of each allow-list)
        $owner = [
            'bd'            => 'bd',
            'superadmin'    => 'country',
            'areaManager'   => 'region',
            'shippingAdmin' => 'shipping_super_admin',
        ];

        foreach ($owner as $roleType) {
            $id = $this->makeAdmin($roleType);
            $this->login($id);

            foreach (self::PORTALS as $portal => $allowed) {
                $shouldPass = in_array($roleType, $allowed, true);
                $this->assertSame(
                    $shouldPass,
                    $this->passesPortal($allowed),
                    "Role '{$roleType}' at portal '{$portal}': expected " . ($shouldPass ? 'ALLOW' : 'DENY') . '.'
                );
            }

            \Encore\Admin\Facades\Admin::guard()->logout();
        }
    }

    /**
     * (2) Each role reaches its OWN portal.
     */
    public function test_each_role_reaches_its_own_portal(): void
    {
        $ownEntry = [
            'bd'                   => self::PORTALS['bd'],
            'country'              => self::PORTALS['superadmin'],
            'sub_country'          => self::PORTALS['superadmin'],
            'region'               => self::PORTALS['areaManager'],
            'sub_region'           => self::PORTALS['areaManager'],
            'shipping_super_admin' => self::PORTALS['shippingAdmin'],
        ];

        foreach ($ownEntry as $roleType => $allowed) {
            $id = $this->makeAdmin($roleType);
            $this->login($id);

            $this->assertTrue(
                $this->passesPortal($allowed),
                "Role '{$roleType}' must reach its own portal."
            );

            \Encore\Admin\Facades\Admin::guard()->logout();
        }
    }

    /**
     * (3) An unauthenticated request is rejected at every portal.
     */
    public function test_unauthenticated_is_rejected_everywhere(): void
    {
        \Encore\Admin\Facades\Admin::guard()->logout();

        foreach (self::PORTALS as $portal => $allowed) {
            $this->assertFalse(
                $this->passesPortal($allowed),
                "An unauthenticated request must be rejected at the {$portal} portal."
            );
        }
    }

    /**
     * (4) Exact match only: a superadmin does NOT slip into the bd portal, and
     * 'administrator' (a valid admin type elsewhere) is denied at all four
     * scoped portals. Guards use in_array(..., true) — no loose/substring match.
     */
    public function test_wrong_but_valid_types_are_denied(): void
    {
        // administrator is a real admin_users type but owns none of these portals.
        $adminId = $this->makeAdmin('administrator');
        $this->login($adminId);

        foreach (self::PORTALS as $portal => $allowed) {
            $this->assertFalse(
                $this->passesPortal($allowed),
                "'administrator' must be denied at the scoped {$portal} portal."
            );
        }

        \Encore\Admin\Facades\Admin::guard()->logout();

        // country must not enter the bd portal.
        $superId = $this->makeAdmin('country');
        $this->login($superId);
        $this->assertFalse(
            $this->passesPortal(self::PORTALS['bd']),
            'A country must not enter the bd portal.'
        );
    }
}
