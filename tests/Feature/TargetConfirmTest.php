<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminUser;
use App\Models\Target;
use App\Models\TargetEdit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TargetConfirmTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode('12345678901234567890123456789012')]);
        config(['app.debug' => true]);
        config(['cache.default' => 'array']);
        Cache::flush();

        if (Schema::hasTable('targets')) {
            if (!Schema::hasColumn('targets', 'under_edit')) {
                Schema::table('targets', function ($table) {
                    $table->boolean('under_edit')->default(false);
                });
            }
            if (!Schema::hasColumn('targets', 'edit_id')) {
                Schema::table('targets', function ($table) {
                    $table->unsignedBigInteger('edit_id')->nullable();
                });
            }
        }
    }

    private function createSuperAdmin(): AdminUser
    {
        $id = DB::table('admin_users')->insertGetId([
            'username'         => 'qa-superadmin-' . uniqid(),
            'password'         => bcrypt('password'),
            'name'             => 'QA Super Admin',
            'type'             => 'employee',
            'di'               => 0,
            'is_frozen_wallet' => 0,
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

    public function test_targets_confirm_route_executes_without_static_call_exception(): void
    {
        $admin = $this->createSuperAdmin();
        $adminModel = Admin::find($admin->id);
        \Encore\Admin\Facades\Admin::guard()->setUser($adminModel);
        $this->actingAs($adminModel, 'admin');

        \Illuminate\Support\Facades\Artisan::shouldReceive('call')
            ->with('users:update-salaries')
            ->once()
            ->andReturn(0);

        $controller = new \App\Admin\Controllers\TargetController();
        $response = $controller->confirm();

        // Must return a RedirectResponse without throwing Non-static method Encore\Admin\Admin::user()
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_target_excel_export_returns_valid_download_response(): void
    {
        $admin = $this->createSuperAdmin();
        $adminModel = Admin::find($admin->id);
        \Encore\Admin\Facades\Admin::guard()->setUser($adminModel);
        $this->actingAs($adminModel, 'admin');

        $request = \Illuminate\Http\Request::create('/admin/download-target-excel', 'GET', [
            'columns' => ['target_no', 'diamonds', 'usd', 'agency_share', 'db_percentage', 'hours', 'days', 'reels', 'moments']
        ]);

        $controller = new \App\Admin\Controllers\TargetController();
        $response = $controller->downloadTargetExcel($request);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('target_data_', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_target_pdf_export_returns_valid_pdf_response(): void
    {
        $admin = $this->createSuperAdmin();
        $adminModel = Admin::find($admin->id);
        \Encore\Admin\Facades\Admin::guard()->setUser($adminModel);
        $this->actingAs($adminModel, 'admin');

        $request = \Illuminate\Http\Request::create('/admin/download-target-pdf', 'GET', [
            'columns' => ['target_no', 'diamonds', 'usd', 'agency_share', 'db_percentage', 'hours', 'days', 'reels', 'moments']
        ]);

        $controller = new \App\Admin\Controllers\TargetController();
        $response = $controller->downloadTargetPdf($request);

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('target_data_', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
