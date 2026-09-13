<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminUser;
use App\Models\ShippingAdminTransaction;
use App\Models\ShippingAgency;
use App\Models\ShippingSuperAdmin;
use App\Models\User;
use App\Services\MainAdminWalletService;
use App\Services\ShippingSuperAdminWalletService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MainAdminChargeAgencyFundingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode('12345678901234567890123456789012')]);
        config(['app.debug' => true]);
        config(['cache.default' => 'array']);
        Cache::flush();

        if (function_exists('settings')) {
            \App\Helpers\Common::withMoneyKeyWrite(fn() => settings()->set(ShippingSuperAdminWalletService::SHIPPING_STOP_CHARGE_KEY, '0'));
        }

        if (!Schema::hasColumn('admin_users', 'di')) {
            Schema::table('admin_users', function ($table) {
                $table->bigInteger('di')->default(0);
            });
        }
        if (!Schema::hasColumn('admin_users', 'type')) {
            Schema::table('admin_users', function ($table) {
                $table->string('type', 50)->nullable();
            });
        }
        if (!Schema::hasColumn('admin_users', 'is_frozen_wallet')) {
            Schema::table('admin_users', function ($table) {
                $table->tinyInteger('is_frozen_wallet')->default(0);
            });
        }
        if (!Schema::hasColumn('admin_users', 'country_id')) {
            Schema::table('admin_users', function ($table) {
                $table->unsignedBigInteger('country_id')->nullable();
            });
        }
        if (!Schema::hasTable('shipping_admin_transactions')) {
            Schema::create('shipping_admin_transactions', function ($table) {
                $table->bigIncrements('id');
                $table->string('operation_uuid', 64);
                $table->string('type', 40);
                $table->string('operation', 20);
                $table->string('sender_type', 40);
                $table->unsignedBigInteger('sender_id');
                $table->string('receiver_type', 40);
                $table->unsignedBigInteger('receiver_id');
                $table->bigInteger('coins');
                $table->bigInteger('before_amount')->default(0);
                $table->bigInteger('after_amount')->default(0);
                $table->unsignedBigInteger('country_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['operation_uuid', 'type'], 'shipping_admin_tx_uuid_type_unique');
                $table->index('sender_id');
                $table->index('receiver_id');
            });
        }
        if (!Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function ($table) {
                $table->bigIncrements('id');
                $table->string('name', 50);
                $table->string('slug', 50)->unique();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('admin_role_users')) {
            Schema::create('admin_role_users', function ($table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->index(['role_id', 'user_id']);
            });
        }
        if (!Schema::hasTable('admin_permissions')) {
            Schema::create('admin_permissions', function ($table) {
                $table->bigIncrements('id');
                $table->string('name', 50);
                $table->string('slug', 50)->unique();
                $table->string('http_method')->nullable();
                $table->text('http_path')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('admin_role_permissions')) {
            Schema::create('admin_role_permissions', function ($table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');
                $table->timestamps();
                $table->index(['role_id', 'permission_id']);
            });
        }
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function ($table) {
                $table->bigIncrements('id');
                $table->string('key')->index();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(['key' => ShippingSuperAdminWalletService::SHIPPING_STOP_CHARGE_KEY], ['value' => '0']);
        }
    }

    private function createMainAdmin(int $di = 10000, int $isFrozenWallet = 0): AdminUser
    {
        $id = DB::table('admin_users')->insertGetId([
            'username'         => 'qa-main-admin-' . uniqid(),
            'password'         => bcrypt('password'),
            'name'             => 'QA Main Admin',
            'type'             => 'employee',
            'di'               => $di,
            'is_frozen_wallet' => $isFrozenWallet,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        if (Schema::hasTable('admin_roles') && Schema::hasTable('admin_role_users')) {
            $roleId = DB::table('admin_roles')->where('slug', 'administrator')->value('id');
            if (!$roleId) {
                $roleId = DB::table('admin_roles')->insertGetId([
                    'name' => 'Administrator', 'slug' => 'administrator',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('admin_role_users')->updateOrInsert([
                'role_id' => $roleId, 'user_id' => $id,
            ]);

            if (Schema::hasTable('admin_permissions') && Schema::hasTable('admin_role_permissions')) {
                $permId = DB::table('admin_permissions')->where('slug', '*')->value('id');
                if (!$permId) {
                    $permId = DB::table('admin_permissions')->insertGetId([
                        'name' => 'All Permission', 'slug' => '*', 'http_method' => '', 'http_path' => '*',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                DB::table('admin_role_permissions')->updateOrInsert([
                    'role_id' => $roleId, 'permission_id' => $permId,
                ]);
            }
        }

        return AdminUser::find($id);
    }

    private function createSubPortalAdmin(string $type = 'country', int $di = 5000): AdminUser
    {
        $id = DB::table('admin_users')->insertGetId([
            'username'         => 'qa-' . $type . '-' . uniqid(),
            'password'         => bcrypt('password'),
            'name'             => 'QA ' . $type,
            'type'             => $type,
            'di'               => $di,
            'is_frozen_wallet' => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return AdminUser::find($id);
    }

    private function createShippingAgency(int $coins = 100, int $isFrozen = 0, int $status = 1): ShippingAgency
    {
        $userAttrs = [
            'uuid'       => (string) rand(1000000, 9999999),
            'name'       => 'Agency Owner ' . rand(100, 999),
            'di'         => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('users', 'country_id')) {
            $userAttrs['country_id'] = 1;
        }
        $ownerId = DB::table('users')->insertGetId($userAttrs);

        $agencyAttrs = [
            'name'            => 'QA Shipping Agency ' . rand(100, 999),
            'app_owner_id'    => $ownerId,
            'type'            => 2,
            'Shipping_agency' => 1,
            'coins'           => $coins,
            'status'          => $status,
            'is_frozen'       => $isFrozen,
            'created_at'      => now(),
            'updated_at'      => now(),
        ];
        if (Schema::hasColumn('agencies', 'country_id')) {
            $agencyAttrs['country_id'] = 1;
        }

        $agencyId = DB::table('agencies')->insertGetId($agencyAttrs);

        DB::table('users')->where('id', $ownerId)->update(['agency_id' => $agencyId]);

        return ShippingAgency::withoutGlobalScopes()->find($agencyId);
    }

    public function test_main_admin_can_fund_charge_agency_successfully(): void
    {
        $admin = $this->createMainAdmin(10000);
        $agency = $this->createShippingAgency(500);
        $uuid = 'test-fund-' . uniqid();

        $service = app(MainAdminWalletService::class);
        $result = $service->fundChargeAgency($admin, $agency, 2000, $uuid, 'Initial test funding');

        $this->assertTrue($result);

        $freshAdmin = DB::table('admin_users')->where('id', $admin->id)->first();
        $freshAgency = DB::table('agencies')->where('id', $agency->id)->first();

        // Main Admin di remains unchanged (issuance model)
        $this->assertEquals(10000, $freshAdmin->di);
        $this->assertEquals(2500, $freshAgency->coins);

        // Exactly one issuance ledger entry (MAIN_FUND_IN), no fake OUT leg
        $txCount = DB::table('shipping_admin_transactions')->where('operation_uuid', $uuid)->count();
        $this->assertEquals(1, $txCount);

        $inTx = DB::table('shipping_admin_transactions')
            ->where('operation_uuid', $uuid)
            ->where('type', ShippingAdminTransaction::MAIN_FUND_IN)
            ->first();

        $this->assertNotNull($inTx);
        $this->assertEquals('main_fund', $inTx->operation);
        $this->assertEquals('main_admin', $inTx->sender_type);
        $this->assertEquals($admin->id, $inTx->sender_id);
        $this->assertEquals('shipping_agency', $inTx->receiver_type);
        $this->assertEquals($agency->id, $inTx->receiver_id);
        $this->assertEquals(2000, $inTx->coins);
        $this->assertEquals(500, $inTx->before_amount);
        $this->assertEquals(2500, $inTx->after_amount);
    }

    public function test_main_admin_can_fund_amount_larger_than_current_di(): void
    {
        $admin = $this->createMainAdmin(50); // Admin only has 50 di
        $agency = $this->createShippingAgency(100);
        $uuid = 'test-exceed-di-' . uniqid();

        $service = app(MainAdminWalletService::class);
        $result = $service->fundChargeAgency($admin, $agency, 500000, $uuid);

        $this->assertTrue($result);

        $freshAdmin = DB::table('admin_users')->where('id', $admin->id)->first();
        $freshAgency = DB::table('agencies')->where('id', $agency->id)->first();

        $this->assertEquals(50, $freshAdmin->di);
        $this->assertEquals(500100, $freshAgency->coins);
    }

    public function test_funding_is_idempotent_on_operation_uuid(): void
    {
        $admin = $this->createMainAdmin(10000);
        $agency = $this->createShippingAgency(1000);
        $uuid = 'test-idempotent-' . uniqid();

        $service = app(MainAdminWalletService::class);
        $firstRun = $service->fundChargeAgency($admin, $agency, 3000, $uuid, 'First execution');
        $this->assertTrue($firstRun);

        $secondRun = $service->fundChargeAgency($admin, $agency, 3000, $uuid, 'Second execution retry');
        $this->assertFalse($secondRun);

        $freshAdmin = DB::table('admin_users')->where('id', $admin->id)->first();
        $freshAgency = DB::table('agencies')->where('id', $agency->id)->first();

        $this->assertEquals(10000, $freshAdmin->di);
        $this->assertEquals(4000, $freshAgency->coins);

        $txCount = DB::table('shipping_admin_transactions')->where('operation_uuid', $uuid)->count();
        $this->assertEquals(1, $txCount);
    }

    public function test_funding_fails_when_agency_is_frozen(): void
    {
        $admin = $this->createMainAdmin(5000);
        $agency = $this->createShippingAgency(100, 1, 1); // is_frozen = 1
        $uuid = 'test-frozen-agency-' . uniqid();

        $service = app(MainAdminWalletService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('it_agency_freez_charge'));

        $service->fundChargeAgency($admin, $agency, 500, $uuid);
    }

    public function test_funding_fails_when_agency_is_inactive(): void
    {
        $admin = $this->createMainAdmin(5000);
        $agency = $this->createShippingAgency(100, 0, 0); // status = 0
        $uuid = 'test-inactive-agency-' . uniqid();

        $service = app(MainAdminWalletService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('This agency is not active'));

        $service->fundChargeAgency($admin, $agency, 500, $uuid);
    }

    public function test_post_fund_endpoint_via_http(): void
    {
        $admin = $this->createMainAdmin(5000);
        $agency = $this->createShippingAgency(200);
        $uuid = 'test-http-' . uniqid();

        $adminModel = Admin::find($admin->id);

        $response = $this->actingAs($adminModel, 'admin')
            ->withoutMiddleware([\App\Http\Middleware\AdminIpMiddleware::class])
            ->postJson(route('admin.charge-agencies.fund'), [
                'agency_id'      => $agency->id,
                'amount'         => 800,
                'operation_uuid' => $uuid,
                'reason'         => 'Test funding via HTTP',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'coins'  => 1000,
        ]);

        $freshAdmin = DB::table('admin_users')->where('id', $admin->id)->first();
        $freshAgency = DB::table('agencies')->where('id', $agency->id)->first();

        $this->assertEquals(5000, $freshAdmin->di);
        $this->assertEquals(1000, $freshAgency->coins);
    }

    public function test_sub_portal_roles_cannot_call_main_admin_fund_endpoint(): void
    {
        $cm = $this->createSubPortalAdmin('country');
        $agency = $this->createShippingAgency(200);
        $uuid = 'test-cm-blocked-' . uniqid();

        $cmModel = Admin::find($cm->id);

        // Sub-portal user attempting to hit Main Admin route gets redirected to their portal login by AuthenticateWeb
        $response = $this->actingAs($cmModel, 'admin')
            ->withoutMiddleware([\App\Http\Middleware\AdminIpMiddleware::class])
            ->postJson(route('admin.charge-agencies.fund'), [
                'agency_id'      => $agency->id,
                'amount'         => 500,
                'operation_uuid' => $uuid,
            ]);

        $this->assertTrue(in_array($response->status(), [302, 403], true));

        // When AuthenticateWeb is bypassed, the controller itself blocks with 403
        $controllerBlockedResponse = $this->actingAs($cmModel, 'admin')
            ->withoutMiddleware([\App\Http\Middleware\AdminIpMiddleware::class, \App\Http\Middleware\AuthenticateWeb::class])
            ->postJson(route('admin.charge-agencies.fund'), [
                'agency_id'      => $agency->id,
                'amount'         => 500,
                'operation_uuid' => $uuid,
            ]);

        $controllerBlockedResponse->assertStatus(403);
    }

    public function test_existing_ssa_to_agency_funding_still_works(): void
    {
        $ssaAttrs = [
            'username'         => 'qa-ssa-' . uniqid(),
            'password'         => bcrypt('password'),
            'name'             => 'QA SSA',
            'type'             => ShippingSuperAdmin::TYPE,
            'di'               => 3000,
            'is_frozen_wallet' => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
        if (Schema::hasColumn('admin_users', 'country_id')) {
            $ssaAttrs['country_id'] = 1;
        }

        $ssaAdminId = DB::table('admin_users')->insertGetId($ssaAttrs);
        $ssa = ShippingSuperAdmin::find($ssaAdminId);
        $agency = $this->createShippingAgency(500);
        $uuid = 'test-ssa-regression-' . uniqid();

        $ssaService = new ShippingSuperAdminWalletService();
        $applied = $ssaService->chargeAgency($ssa, $agency, 600, $uuid);

        $this->assertTrue($applied);

        $freshSsa = DB::table('admin_users')->where('id', $ssa->id)->first();
        $freshAgency = DB::table('agencies')->where('id', $agency->id)->first();

        $this->assertEquals(2400, $freshSsa->di);
        $this->assertEquals(1100, $freshAgency->coins);

        $outLeg = ShippingAdminTransaction::where('operation_uuid', $uuid)
            ->where('type', ShippingAdminTransaction::CHARGE_OUT)
            ->first();
        $this->assertNotNull($outLeg);
        $this->assertEquals('shipping_super_admin', $outLeg->sender_type);
    }

    public function test_fund_agency_coins_row_action_renders_proper_html_without_nested_li(): void
    {
        $agency = $this->createShippingAgency(1234);

        $action = new \App\Admin\Actions\FundAgencyCoinsAction();
        $action->setRow($agency);

        $html = $action->render();

        // Must NOT have outer <li> tags because DropdownActions wraps it
        $this->assertStringNotContainsString('<li>', $html);
        $this->assertStringNotContainsString('</li>', $html);

        $this->assertStringContainsString('fund-agency-coins-btn', $html);
        $this->assertStringContainsString('data-id="' . $agency->id . '"', $html);
        $this->assertStringContainsString('data-name="' . htmlspecialchars($agency->name, ENT_QUOTES) . '"', $html);
        $this->assertStringContainsString('data-coins="1234"', $html);
        $this->assertStringContainsString(__('Fund Coins'), $html);

        // Verify DropdownActions view rendering
        $dropdownHtml = view('admin::grid.actions.dropdown', [
            'default' => [],
            'custom'  => [$action],
        ])->render();

        // Exactly one Fund Coins button inside the dropdown
        $this->assertEquals(1, substr_count($dropdownHtml, 'fund-agency-coins-btn'));
        // No nested <li> tags
        $this->assertStringNotContainsString('<li><li>', $dropdownHtml);
        $this->assertStringNotContainsString('<li><li', $dropdownHtml);
        $this->assertStringContainsString('<li><a href="javascript:void(0);"', $dropdownHtml);

        $modalHtml = view('admin.grid.shipping_agency.fund_modal')->render();
        $this->assertStringContainsString('mainAdminFundModal', $modalHtml);
        $this->assertStringContainsString('fund-agency-coins-btn', $modalHtml);
        $this->assertStringContainsString('mainAdminRemoveModal', $modalHtml);
        $this->assertStringContainsString('remove-agency-coins-btn', $modalHtml);
        $this->assertStringContainsString(admin_url('charge-agencies/fund'), $modalHtml);
        $this->assertStringContainsString(admin_url('charge-agencies/remove'), $modalHtml);
    }

    public function test_non_main_admin_roles_rejected_from_fund_coins_action_and_endpoint(): void
    {
        $disallowedTypes = ['country', 'sub_country', 'shipping_super_admin', 'shipping_agency', 'bd', 'region', 'sub_region'];
        $agency = $this->createShippingAgency(500);

        foreach ($disallowedTypes as $type) {
            $subAdmin = $this->createSubPortalAdmin($type);
            $subAdminModel = Admin::find($subAdmin->id);

            $response = $this->actingAs($subAdminModel, 'admin')
                ->withoutMiddleware([\App\Http\Middleware\AdminIpMiddleware::class, \App\Http\Middleware\AuthenticateWeb::class])
                ->postJson(route('admin.charge-agencies.fund'), [
                    'agency_id'      => $agency->id,
                    'amount'         => 100,
                    'operation_uuid' => 'test-reject-' . $type . '-' . uniqid(),
                ]);

            $this->assertEquals(403, $response->status(), "User type '{$type}' should be rejected with 403");
        }
    }

    public function test_main_admin_can_remove_coins_from_charge_agency_successfully(): void
    {
        $admin = $this->createMainAdmin(10000);
        $agency = $this->createShippingAgency(5000);
        $uuid = 'test-remove-' . uniqid();

        $service = app(MainAdminWalletService::class);
        $result = $service->removeCoinsFromChargeAgency($admin, $agency, 2000, $uuid, 'Deduct excess allocation');

        $this->assertTrue($result);

        $freshAdmin = DB::table('admin_users')->where('id', $admin->id)->first();
        $freshAgency = DB::table('agencies')->where('id', $agency->id)->first();

        // Main Admin di remains unchanged (platform adjustment, not wallet credit)
        $this->assertEquals(10000, $freshAdmin->di);
        $this->assertEquals(3000, $freshAgency->coins);

        // Exactly one removal ledger entry (MAIN_REMOVE_AGENCY)
        $txCount = DB::table('shipping_admin_transactions')->where('operation_uuid', $uuid)->count();
        $this->assertEquals(1, $txCount);

        $tx = DB::table('shipping_admin_transactions')
            ->where('operation_uuid', $uuid)
            ->where('type', ShippingAdminTransaction::MAIN_REMOVE_AGENCY)
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals('main_remove', $tx->operation);
        $this->assertEquals('shipping_agency', $tx->sender_type);
        $this->assertEquals($agency->id, $tx->sender_id);
        $this->assertEquals('main_admin', $tx->receiver_type);
        $this->assertEquals($admin->id, $tx->receiver_id);
        $this->assertEquals(2000, $tx->coins);
        $this->assertEquals(5000, $tx->before_amount);
        $this->assertEquals(3000, $tx->after_amount);
        $this->assertEquals($admin->id, $tx->created_by);
    }

    public function test_remove_coins_fails_when_amount_exceeds_agency_balance(): void
    {
        $admin = $this->createMainAdmin(10000);
        $agency = $this->createShippingAgency(1000);
        $uuid = 'test-remove-exceed-' . uniqid();

        $service = app(MainAdminWalletService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('balance not enough'));

        $service->removeCoinsFromChargeAgency($admin, $agency, 1500, $uuid);
    }

    public function test_remove_coins_fails_for_frozen_agency(): void
    {
        $admin = $this->createMainAdmin(10000);
        $agency = $this->createShippingAgency(5000, 1); // frozen
        $uuid = 'test-remove-frozen-' . uniqid();

        $service = app(MainAdminWalletService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('it_agency_freez_charge'));

        $service->removeCoinsFromChargeAgency($admin, $agency, 500, $uuid);
    }

    public function test_remove_coins_fails_for_inactive_agency(): void
    {
        $admin = $this->createMainAdmin(10000);
        $agency = $this->createShippingAgency(5000, 0, 0); // inactive status=0
        $uuid = 'test-remove-inactive-' . uniqid();

        $service = app(MainAdminWalletService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('This agency is not active'));

        $service->removeCoinsFromChargeAgency($admin, $agency, 500, $uuid);
    }

    public function test_remove_coins_fails_for_zero_or_negative_amount(): void
    {
        $admin = $this->createMainAdmin(10000);
        $agency = $this->createShippingAgency(5000);
        $service = app(MainAdminWalletService::class);

        try {
            $service->removeCoinsFromChargeAgency($admin, $agency, 0, 'test-zero-' . uniqid());
            $this->fail('Expected exception for zero amount');
        } catch (\RuntimeException $e) {
            $this->assertEquals(__('This value is not allowed'), $e->getMessage());
        }

        try {
            $service->removeCoinsFromChargeAgency($admin, $agency, -100, 'test-neg-' . uniqid());
            $this->fail('Expected exception for negative amount');
        } catch (\RuntimeException $e) {
            $this->assertEquals(__('This value is not allowed'), $e->getMessage());
        }
    }

    public function test_remove_coins_duplicate_operation_uuid_idempotent(): void
    {
        $admin = $this->createMainAdmin(10000);
        $agency = $this->createShippingAgency(5000);
        $uuid = 'test-remove-idempotent-' . uniqid();

        $service = app(MainAdminWalletService::class);

        // First removal: succeeds
        $first = $service->removeCoinsFromChargeAgency($admin, $agency, 1000, $uuid);
        $this->assertTrue($first);
        $this->assertEquals(4000, DB::table('agencies')->where('id', $agency->id)->value('coins'));

        // Second removal with same uuid: returns false, does not deduct again
        $second = $service->removeCoinsFromChargeAgency($admin, $agency, 1000, $uuid);
        $this->assertFalse($second);
        $this->assertEquals(4000, DB::table('agencies')->where('id', $agency->id)->value('coins'));
        $this->assertEquals(1, DB::table('shipping_admin_transactions')->where('operation_uuid', $uuid)->count());
    }

    public function test_remove_coins_endpoint_success_and_validations(): void
    {
        $admin = $this->createMainAdmin(10000);
        $adminModel = Admin::find($admin->id);
        $agency = $this->createShippingAgency(3000);
        $uuid = 'test-endpoint-remove-' . uniqid();

        $response = $this->actingAs($adminModel, 'admin')
            ->withoutMiddleware([\App\Http\Middleware\AdminIpMiddleware::class, \App\Http\Middleware\AuthenticateWeb::class])
            ->postJson(route('admin.charge-agencies.remove'), [
                'agency_id'      => $agency->id,
                'amount'         => 1000,
                'operation_uuid' => $uuid,
                'reason'         => 'Audit adjustment',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => true,
            'message' => __('Coins removed successfully'),
            'coins'   => 2000,
        ]);

        $this->assertEquals(2000, DB::table('agencies')->where('id', $agency->id)->value('coins'));

        // Re-post same uuid -> Already processed
        $retryResponse = $this->actingAs($adminModel, 'admin')
            ->withoutMiddleware([\App\Http\Middleware\AdminIpMiddleware::class, \App\Http\Middleware\AuthenticateWeb::class])
            ->postJson(route('admin.charge-agencies.remove'), [
                'agency_id'      => $agency->id,
                'amount'         => 1000,
                'operation_uuid' => $uuid,
            ]);

        $retryResponse->assertStatus(200);
        $retryResponse->assertJson([
            'status'  => true,
            'message' => __('Already processed'),
        ]);
        $this->assertEquals(2000, DB::table('agencies')->where('id', $agency->id)->value('coins'));
    }

    public function test_non_main_admin_roles_rejected_from_remove_coins_endpoint(): void
    {
        $disallowedTypes = ['country', 'sub_country', 'shipping_super_admin', 'shipping_agency', 'bd', 'region', 'sub_region'];
        $agency = $this->createShippingAgency(5000);

        foreach ($disallowedTypes as $type) {
            $subAdmin = $this->createSubPortalAdmin($type);
            $subAdminModel = Admin::find($subAdmin->id);

            $response = $this->actingAs($subAdminModel, 'admin')
                ->withoutMiddleware([\App\Http\Middleware\AdminIpMiddleware::class, \App\Http\Middleware\AuthenticateWeb::class])
                ->postJson(route('admin.charge-agencies.remove'), [
                    'agency_id'      => $agency->id,
                    'amount'         => 100,
                    'operation_uuid' => 'test-reject-remove-' . $type . '-' . uniqid(),
                ]);

            $this->assertEquals(403, $response->status(), "User type '{$type}' should be rejected from removeCoins with 403");
        }
    }

    public function test_remove_agency_coins_row_action_renders_proper_html(): void
    {
        $agency = $this->createShippingAgency(4567);

        $action = new \App\Admin\Actions\RemoveAgencyCoinsAction();
        $action->setRow($agency);

        $html = $action->render();

        $this->assertStringNotContainsString('<li>', $html);
        $this->assertStringNotContainsString('</li>', $html);
        $this->assertStringContainsString('remove-agency-coins-btn', $html);
        $this->assertStringContainsString('data-id="' . $agency->id . '"', $html);
        $this->assertStringContainsString('data-name="' . htmlspecialchars($agency->name, ENT_QUOTES) . '"', $html);
        $this->assertStringContainsString('data-coins="4567"', $html);
        $this->assertStringContainsString(__('Remove Coins'), $html);

        // Verify DropdownActions view rendering
        $dropdownHtml = view('admin::grid.actions.dropdown', [
            'default' => [],
            'custom'  => [$action],
        ])->render();

        $this->assertEquals(1, substr_count($dropdownHtml, 'remove-agency-coins-btn'));
        $this->assertStringNotContainsString('<li><li>', $dropdownHtml);
        $this->assertStringContainsString('<li><a href="javascript:void(0);"', $dropdownHtml);
    }

    public function test_grid_actions_closure_executes_cleanly(): void
    {
        $admin = $this->createMainAdmin(5000);
        $adminModel = Admin::find($admin->id);
        \Encore\Admin\Facades\Admin::guard()->setUser($adminModel);
        $this->actingAs($adminModel, 'admin');

        $agency = $this->createShippingAgency(100);

        $controller = new \App\Admin\Controllers\AppearChargerAgencyController();
        $refMethod = new \ReflectionMethod($controller, 'grid');
        $refMethod->setAccessible(true);
        $grid = $refMethod->invoke($controller);

        $this->assertInstanceOf(\Encore\Admin\Grid::class, $grid);

        // Check Available Coins column exists in grid
        $columnNames = array_map(function ($col) {
            return $col->getName();
        }, $grid->columns()->all());
        $this->assertContains('coins', $columnNames, 'Grid must contain Available Coins (coins) column');

        // Render grid actions for the row
        $column = new \Encore\Admin\Grid\Column('__actions__', 'Action');
        $column->setGrid($grid);
        $dropdown = new \Encore\Admin\Grid\Displayers\DropdownActions('', $grid, $column, $agency);

        $refProp = new \ReflectionProperty($grid, 'actionsCallback');
        $refProp->setAccessible(true);
        $actionsCallback = $refProp->getValue($grid);
        $this->assertNotNull($actionsCallback);

        // Executing the display callback must render both fund and remove actions
        $rendered = $dropdown->display($actionsCallback);
        $this->assertStringContainsString('fund-agency-coins-btn', $rendered);
        $this->assertStringContainsString('remove-agency-coins-btn', $rendered);
    }
}
