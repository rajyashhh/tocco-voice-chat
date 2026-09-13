<?php

namespace Tests\Feature;

use App\Models\ShippingAgency;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SellerUserSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => true]);
        config(['cache.default' => 'array']);
        Cache::flush();

        if (!Schema::hasTable('image_colors')) {
            Schema::create('image_colors', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('image')->nullable();
                $table->string('color')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users_vips')) {
            if (!Schema::hasColumn('users_vips', 'is_used')) {
                Schema::table('users_vips', function ($table) {
                    $table->tinyInteger('is_used')->default(0);
                });
            }
            if (!Schema::hasColumn('users_vips', 'deleted_at')) {
                Schema::table('users_vips', function ($table) {
                    $table->softDeletes();
                });
            }
        }

        $this->withoutMiddleware([\Illuminate\Routing\Middleware\ThrottleRequests::class]);
        $this->withoutMiddleware([\App\Http\Middleware\Localization::class]);
        $this->withoutMiddleware([\App\Http\Middleware\CheckLatestToken::class]);
        $this->withoutMiddleware([\App\Http\Middleware\GeneralBanMiddleware::class]);
        $this->withoutMiddleware([\App\Http\Middleware\UserBanMiddleware::class]);
    }

    private function createUser(array $attributes = []): User
    {
        $id = DB::table('users')->insertGetId(array_merge([
            'uuid' => (string) rand(10000000, 99999999),
            'name' => 'Test User ' . rand(100, 999),
            'country_id' => 1,
            'di' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return User::find($id);
    }

    private function createShippingAgency(User $owner): ShippingAgency
    {
        $id = DB::table('agencies')->insertGetId([
            'name' => 'Agency ' . rand(100, 999),
            'app_owner_id' => $owner->id,
            'type' => 2,
            'Shipping_agency' => 1,
            'coins' => 1000,
            'status' => 1,
            'is_frozen' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $owner->id)->update(['agency_id' => $id]);

        return ShippingAgency::withoutGlobalScopes()->find($id);
    }

    /**
     * Requirement 1: Seller can search an existing app user by User ID (database id or uuid).
     */
    public function test_seller_can_search_existing_user_by_numeric_id_and_uuid(): void
    {
        $seller = $this->createUser();
        $this->createShippingAgency($seller);

        $targetUuid = (string) rand(10000000, 99999999);
        $targetUser = $this->createUser([
            'uuid' => $targetUuid,
            'name' => 'Recipient Alpha',
        ]);

        Sanctum::actingAs($seller);

        // 1a: Search by numeric database ID
        $responseById = $this->postJson('/api/search-user-agency', [
            'type' => 'user',
            'id' => (string) $targetUser->id,
        ]);

        $responseById->assertStatus(200);
        $usersById = $responseById->json('data.user');
        $this->assertNotEmpty($usersById, 'Searching by numeric ID should return the user.');
        $this->assertEquals($targetUser->id, $usersById[0]['id']);

        // 1b: Search by UUID prefix
        $responseByUuid = $this->postJson('/api/search-user-agency', [
            'type' => 'user',
            'id' => substr($targetUuid, 0, 5),
        ]);

        $responseByUuid->assertStatus(200);
        $usersByUuid = $responseByUuid->json('data.user');
        $this->assertNotEmpty($usersByUuid, 'Searching by UUID prefix should return the user.');
        $this->assertEquals($targetUser->id, $usersByUuid[0]['id']);
    }

    /**
     * Requirement 2: Seller can search an existing app user by name.
     */
    public function test_seller_can_search_existing_user_by_name(): void
    {
        $seller = $this->createUser();
        $this->createShippingAgency($seller);

        $uniqueName = 'ZanzibarSearchableUser' . rand(100, 999);
        $targetUser = $this->createUser([
            'name' => $uniqueName,
        ]);

        Sanctum::actingAs($seller);

        $response = $this->postJson('/api/search-user-agency', [
            'type' => 'user',
            'id' => substr($uniqueName, 0, 10),
        ]);

        $response->assertStatus(200);
        $users = $response->json('data.user');
        $this->assertNotEmpty($users, 'Searching by name prefix should return the user.');
        $found = collect($users)->firstWhere('id', $targetUser->id);
        $this->assertNotNull($found, 'Target user must be in the search results.');
        $this->assertEquals($uniqueName, $found['name']);
    }

    /**
     * Requirement 3: Nonexistent users return an empty result normally without error.
     */
    public function test_nonexistent_user_returns_empty_result(): void
    {
        $seller = $this->createUser();
        $this->createShippingAgency($seller);

        Sanctum::actingAs($seller);

        $response = $this->postJson('/api/search-user-agency', [
            'type' => 'user',
            'id' => 'nonexistent_user_xyz_' . uniqid(),
        ]);

        $response->assertStatus(200);
        $users = $response->json('data.user');
        $this->assertIsArray($users);
        $this->assertEmpty($users, 'Nonexistent query should return an empty list.');
    }

    /**
     * Requirement 4: Existing seller authentication/authorization remains enforced.
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/api/search-user-agency', [
            'type' => 'user',
            'id' => '12345',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Requirement 5: Route registration verification.
     */
    public function test_search_and_charge_routes_are_correctly_registered(): void
    {
        $routes = app('router')->getRoutes();

        $searchRoute = $routes->match(
            \Illuminate\Http\Request::create('/api/search-user-agency', 'POST')
        );
        $this->assertNotNull($searchRoute);
        $this->assertEquals('App\Http\Controllers\Api\V1\ChargeController@getUserAgency', $searchRoute->getActionName());
        $this->assertContains('auth:sanctum', $searchRoute->gatherMiddleware());

        $chargeRoute = $routes->match(
            \Illuminate\Http\Request::create('/api/agencies/charge-agency', 'POST')
        );
        $this->assertNotNull($chargeRoute);
        $this->assertEquals('App\Http\Controllers\Api\V1\ChargeController@chargeFromAgencyToAnother', $chargeRoute->getActionName());
        $this->assertContains('auth:sanctum', $chargeRoute->gatherMiddleware());
    }
}
