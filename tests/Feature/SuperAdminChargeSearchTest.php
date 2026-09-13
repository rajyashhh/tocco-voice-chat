<?php

namespace Tests\Feature;

use App\Enums\Charges\UserTypeEnum;
use App\Models\Charge;
use App\Models\ShippingAgency;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminChargeSearchTest extends TestCase
{
    use DatabaseTransactions;

    private function createUser(): User
    {
        $id = DB::table('users')->insertGetId([
            'uuid' => (string) rand(1000000, 9999999),
            'name' => 'User ' . rand(100, 999),
            'country_id' => 1,
            'di' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::find($id);
    }

    private function createShippingAgency(User $owner, string $name = 'Shipping Agency'): ShippingAgency
    {
        $id = DB::table('agencies')->insertGetId([
            'name' => $name . ' ' . rand(100, 999),
            'app_owner_id' => $owner->id,
            'type' => 2,
            'Shipping_agency' => 1,
            'Host_agency' => 0,
            'coins' => 1000,
            'status' => 1,
            'is_frozen' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ShippingAgency::withoutGlobalScopes()->find($id);
    }

    private function createHostAgency(User $owner, string $name = 'Host Agency')
    {
        $id = DB::table('agencies')->insertGetId([
            'name' => $name . ' ' . rand(100, 999),
            'app_owner_id' => $owner->id,
            'type' => 1,
            'Shipping_agency' => 0,
            'Host_agency' => 1,
            'coins' => 0,
            'status' => 1,
            'is_frozen' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function createAgencyCharge(int $chargerId, int $agencyId, int $amount = 500): Charge
    {
        return Charge::create([
            'charger_id' => $chargerId,
            'charger_type' => UserTypeEnum::SUPER_ADMIN,
            'user_id' => $agencyId,
            'agency_id' => null,
            'user_type' => 'agency',
            'amount' => $amount,
            'amount_type' => 2,
            'usd' => 50,
            'is_used_transferred' => false,
        ]);
    }

    /**
     * Test searching by agency.id returns its charge history.
     */
    public function test_search_by_agency_id_returns_charge_history(): void
    {
        $owner = $this->createUser();
        $agency = $this->createShippingAgency($owner);
        $charge = $this->createAgencyCharge(1, $agency->id, 250);

        // Query resolver directly representing the filter callback
        $input = $agency->id;
        $agencyIds = ShippingAgency::where('id', $input)
            ->orWhere('app_owner_id', $input)
            ->pluck('id')
            ->toArray();

        $results = Charge::where('user_type', 'agency')
            ->whereIn('user_id', !empty($agencyIds) ? $agencyIds : [0])
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($charge->id, $results->first()->id);
        $this->assertEquals($agency->id, $results->first()->user_id);
    }

    /**
     * Test searching by app_owner_id returns the same agency's charge history.
     */
    public function test_search_by_app_owner_id_returns_charge_history(): void
    {
        $owner = $this->createUser();
        $agency = $this->createShippingAgency($owner);
        $charge = $this->createAgencyCharge(1, $agency->id, 350);

        // Query resolver directly using owner's app user ID
        $input = $owner->id;
        $agencyIds = ShippingAgency::where('id', $input)
            ->orWhere('app_owner_id', $input)
            ->pluck('id')
            ->toArray();

        $results = Charge::where('user_type', 'agency')
            ->whereIn('user_id', !empty($agencyIds) ? $agencyIds : [0])
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($charge->id, $results->first()->id);
        $this->assertEquals($agency->id, $results->first()->user_id);
    }

    /**
     * Test that a newly created agency with zero charge history returns an empty result.
     */
    public function test_newly_created_agency_with_zero_charges_returns_empty(): void
    {
        $owner = $this->createUser();
        $agency = $this->createShippingAgency($owner);

        // Search by agency ID
        $agencyIdsByAgencyId = ShippingAgency::where('id', $agency->id)
            ->orWhere('app_owner_id', $agency->id)
            ->pluck('id')
            ->toArray();

        $resultsByAgencyId = Charge::where('user_type', 'agency')
            ->whereIn('user_id', !empty($agencyIdsByAgencyId) ? $agencyIdsByAgencyId : [0])
            ->get();

        $this->assertCount(0, $resultsByAgencyId);

        // Search by owner user ID
        $agencyIdsByOwnerId = ShippingAgency::where('id', $owner->id)
            ->orWhere('app_owner_id', $owner->id)
            ->pluck('id')
            ->toArray();

        $resultsByOwnerId = Charge::where('user_type', 'agency')
            ->whereIn('user_id', !empty($agencyIdsByOwnerId) ? $agencyIdsByOwnerId : [0])
            ->get();

        $this->assertCount(0, $resultsByOwnerId);
    }

    /**
     * Test searching by an invalid or non-existent ID returns no results.
     */
    public function test_search_by_invalid_id_returns_empty(): void
    {
        $invalidId = 99999999;
        $agencyIds = ShippingAgency::where('id', $invalidId)
            ->orWhere('app_owner_id', $invalidId)
            ->pluck('id')
            ->toArray();

        $results = Charge::where('user_type', 'agency')
            ->whereIn('user_id', !empty($agencyIds) ? $agencyIds : [0])
            ->get();

        $this->assertCount(0, $results);
    }

    /**
     * Test searching for a non-shipping agency (Host Agency) returns no results.
     */
    public function test_host_agency_id_is_scoped_out_and_returns_empty(): void
    {
        $owner = $this->createUser();
        $hostAgencyId = $this->createHostAgency($owner);

        // Create charge record for this host agency
        $this->createAgencyCharge(1, $hostAgencyId, 100);

        // Search by host agency ID
        $agencyIds = ShippingAgency::where('id', $hostAgencyId)
            ->orWhere('app_owner_id', $hostAgencyId)
            ->pluck('id')
            ->toArray();

        $this->assertEmpty($agencyIds, 'ShippingAgency scope must exclude Host Agencies');

        $results = Charge::where('user_type', 'agency')
            ->whereIn('user_id', !empty($agencyIds) ? $agencyIds : [0])
            ->get();

        $this->assertCount(0, $results);
    }
}
