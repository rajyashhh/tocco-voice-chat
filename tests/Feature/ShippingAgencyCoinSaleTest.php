<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ShippingAgency;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShippingAgencyCoinSaleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => true]);
        config(['cache.default' => 'array']);
        Cache::flush();
        $this->withoutMiddleware([\Illuminate\Routing\Middleware\ThrottleRequests::class]);
        $this->withoutMiddleware([\App\Http\Middleware\Localization::class]);
        $this->withoutMiddleware([\App\Http\Middleware\CheckLatestToken::class]);
        $this->withoutMiddleware([\App\Http\Middleware\GeneralBanMiddleware::class]);
        $this->withoutMiddleware([\App\Http\Middleware\UserBanMiddleware::class]);
    }

    private function createUser(int $countryId, int $di = 100): User
    {
        $id = DB::table('users')->insertGetId([
            'uuid' => (string) rand(1000000, 9999999),
            'name' => 'Test User ' . rand(100, 999),
            'country_id' => $countryId,
            'di' => $di,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::find($id);
    }

    private function createShippingAgency(User $owner, int $countryId, int $coins = 500, int $isFrozen = 0): ShippingAgency
    {
        $id = DB::table('agencies')->insertGetId([
            'name' => 'Agency ' . rand(100, 999),
            'app_owner_id' => $owner->id,
            'type' => 2,
            'Shipping_agency' => 1,
            'coins' => $coins,
            'status' => 1,
            'is_frozen' => $isFrozen,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $owner->id)->update(['agency_id' => $id]);

        return ShippingAgency::withoutGlobalScopes()->find($id);
    }

    public function test_same_country_transfer_succeeds()
    {
        $owner = $this->createUser(10);
        $receiver = $this->createUser(10, 100);
        $agency = $this->createShippingAgency($owner, 10, 500);

        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $response = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => 'test-uuid-same-country-12345',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(400, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(200, (int) DB::table('users')->where('id', $receiver->id)->value('di'));

        $this->assertDatabaseHas('charges', [
            'charger_id' => $agency->id,
            'user_id' => $receiver->id,
            'amount' => 100,
        ]);
    }

    public function test_cross_country_transfer_is_rejected()
    {
        $owner = $this->createUser(10);
        $receiver = $this->createUser(20, 100);
        $agency = $this->createShippingAgency($owner, 10, 500);

        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $response = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => 'test-uuid-cross-country-12345',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        $this->assertEquals(500, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(100, (int) DB::table('users')->where('id', $receiver->id)->value('di'));
    }

    public function test_same_operation_uuid_twice_executes_only_once()
    {
        $owner = $this->createUser(10);
        $receiver = $this->createUser(10, 100);
        $agency = $this->createShippingAgency($owner, 10, 500);

        $uuid = 'test-uuid-idempotency-99999';

        // Request 1
        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $res1 = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => $uuid,
        ]);
        $res1->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals(400, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(200, (int) DB::table('users')->where('id', $receiver->id)->value('di'));
        $this->assertEquals(1, Charge::where('charger_id', $agency->id)->where('user_id', $receiver->id)->count());

        // Request 2 (Duplicate operation_uuid)
        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $res2 = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => $uuid,
        ]);
        $res2->assertStatus(200)->assertJson(['success' => true]);

        // Balances and charges count must remain UNCHANGED (exactly 1 debit, 1 credit, 1 charge record)
        $this->assertEquals(400, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(200, (int) DB::table('users')->where('id', $receiver->id)->value('di'));
        $this->assertEquals(1, Charge::where('charger_id', $agency->id)->where('user_id', $receiver->id)->count());
    }

    public function test_different_operation_uuids_execute_separately()
    {
        $owner = $this->createUser(10);
        $receiver = $this->createUser(10, 100);
        $agency = $this->createShippingAgency($owner, 10, 500);

        // Request 1 with UUID 1
        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $res1 = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => 'test-uuid-diff-11111',
        ]);
        $res1->assertStatus(200)->assertJson(['success' => true]);

        // Request 2 with UUID 2
        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $res2 = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => 'test-uuid-diff-22222',
        ]);
        $res2->assertStatus(200)->assertJson(['success' => true]);

        // Total 2 debits and 2 credits
        $this->assertEquals(300, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(300, (int) DB::table('users')->where('id', $receiver->id)->value('di'));
        $this->assertEquals(2, Charge::where('charger_id', $agency->id)->where('user_id', $receiver->id)->count());
    }

    public function test_insufficient_balance_is_rejected()
    {
        $owner = $this->createUser(10);
        $receiver = $this->createUser(10, 100);
        $agency = $this->createShippingAgency($owner, 10, 50);

        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $response = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => 'test-uuid-insufficient-12345',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertEquals(50, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(100, (int) DB::table('users')->where('id', $receiver->id)->value('di'));
    }

    public function test_frozen_agency_is_rejected()
    {
        $owner = $this->createUser(10);
        $receiver = $this->createUser(10, 100);
        $agency = $this->createShippingAgency($owner, 10, 500, 1);

        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $response = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => 'test-uuid-frozen-12345',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertEquals(500, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(100, (int) DB::table('users')->where('id', $receiver->id)->value('di'));
    }

    public function test_non_owner_is_rejected()
    {
        $owner = $this->createUser(10);
        $otherUser = $this->createUser(10);
        $receiver = $this->createUser(10, 100);
        $agency = $this->createShippingAgency($owner, 10, 500);

        \Laravel\Sanctum\Sanctum::actingAs($otherUser);
        $response = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => 'test-uuid-nonowner-12345',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertEquals(500, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(100, (int) DB::table('users')->where('id', $receiver->id)->value('di'));
    }

    public function test_invalid_operation_uuid_format_is_rejected()
    {
        $owner = $this->createUser(10);
        $receiver = $this->createUser(10, 100);
        $agency = $this->createShippingAgency($owner, 10, 500);

        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $response = $this->postJson('/api/v1/chargeFromAgencyToAnother', [
            'id' => $receiver->id,
            'type' => 'user',
            'amount' => 100,
            'operation_uuid' => 'short', // less than 10 characters
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertEquals(500, (int) DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(100, (int) DB::table('users')->where('id', $receiver->id)->value('di'));
    }
}
