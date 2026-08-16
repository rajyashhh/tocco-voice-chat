<?php

namespace Tests\Feature\UtdQa;

use App\Bd\Controllers\AgencyController;
use App\Models\User;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * H1 - BD portal: an agency profile is scoped to the requesting BD.
 *
 * Target:
 *   app/Bd/Controllers/AgencyController.php::profile
 *   The fix scopes the lookup with ->where('bd_id', $user->id) (for both the
 *   normal Agency and the ShippingAgency fallback); a non-owned id resolves to
 *   null and the method throws "Agency not found" before any tenant data is
 *   read or rendered.
 *
 * What we prove:
 *  (A) BD-A opening an agency where agencies.bd_id = A -> succeeds (the method
 *      returns an Encore Content object, i.e. it reached render without the
 *      not-found throw).
 *  (B) BD-B opening that same agency (bd_id = A) -> rejected: throws
 *      "Agency not found", and no members / owner data is returned.
 *
 * The profile() call runs through the real controller (no re-implementation),
 * so if the ->where('bd_id', ...) scope were removed this guard would fail.
 */
class BdAgencyProfileIsolationTest extends UtdQaTestCase
{
    private function profileFor(User $bd, int $agencyId, string $tab = 'members'): Content
    {
        Auth::login($bd);
        $request = \Illuminate\Http\Request::create('/bd/agencies/profile/' . $agencyId, 'GET', ['tab' => $tab]);
        // request() helper reads from the current request; bind it.
        app()->instance('request', $request);

        return app(AgencyController::class)->profile($agencyId, $request, new Content());
    }

    public function test_bd_can_open_own_agency_profile(): void
    {
        $bd    = $this->makeUser();
        $owner = $this->makeUser();
        $agencyId = $this->insertAgency([
            'app_owner_id' => $owner->id,
            'bd_id'        => $bd->id,
            'type'         => 1,
        ]);

        $result = $this->profileFor($bd, $agencyId);

        $this->assertInstanceOf(
            Content::class,
            $result,
            'A BD must be able to open the profile of an agency they own (bd_id match).'
        );
    }

    public function test_bd_cannot_open_foreign_agency_profile(): void
    {
        $bdA   = $this->makeUser();
        $bdB   = $this->makeUser();
        $owner = $this->makeUser();
        $agencyId = $this->insertAgency([
            'app_owner_id' => $owner->id,
            'bd_id'        => $bdA->id, // belongs to BD-A
            'type'         => 1,
        ]);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Agency not found');

        // BD-B must NOT see BD-A's agency.
        $this->profileFor($bdB, $agencyId);
    }

    /**
     * Negative case also holds for the ShippingAgency (type=2) fallback branch:
     * a foreign shipping agency is not leaked to another BD.
     */
    public function test_bd_cannot_open_foreign_shipping_agency_profile(): void
    {
        $bdA   = $this->makeUser();
        $bdB   = $this->makeUser();
        $owner = $this->makeUser();
        $agencyId = $this->insertAgency([
            'app_owner_id' => $owner->id,
            'bd_id'        => $bdA->id,
            'type'         => 2, // shipping
        ]);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Agency not found');

        $this->profileFor($bdB, $agencyId, 'charges');
    }
}