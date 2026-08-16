<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the Shipping Super Admin portal's role, permissions and sidebar menus.
 *
 * Mirrors AdminRoleBDSeeder but for the coin-only shipping layer. Fully
 * idempotent (updateOrInsert throughout); menus are keyed on their uri so a
 * re-run never clobbers unrelated menu ids. Create-only in spirit: it inserts
 * what is missing and leaves existing rows alone.
 */
class ShippingSuperAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Wallet permission consumed by the portal's MainController gate
        // (browse-shipping-super-admin-wallet) plus the coin-charge action.
        $permissions = [
            [
                'name'        => 'browse-shipping-super-admin-wallet',
                'slug'        => 'browse-shipping-super-admin-wallet',
                'http_method' => 'GET',
                'http_path'   => 'shippingAdmin/wallet*',
            ],
            [
                'name'        => 'create-shipping-super-admin-wallet',
                'slug'        => 'create-shipping-super-admin-wallet',
                'http_method' => 'POST',
                'http_path'   => 'shippingAdmin/wallet/charge',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('admin_permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                array_merge($permission, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $role = DB::table('admin_roles')->where('slug', 'shipping_super_admin')->first();
        if (!$role) {
            $roleId = DB::table('admin_roles')->insertGetId([
                'name'       => 'Shipping Super Admin',
                'slug'       => 'shipping_super_admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $roleId = $role->id;
        }

        $permissionIds = DB::table('admin_permissions')
            ->whereIn('slug', array_column($permissions, 'slug'))
            ->pluck('id')
            ->toArray();

        foreach ($permissionIds as $permissionId) {
            DB::table('admin_role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId]
            );
        }

        // No admin_menu rows are seeded here (owner 2026-08-12). The table is
        // shared with the main admin sidebar, so the old root rows ("Home" ->
        // uri shippingAdmin, "Shipping Wallet" -> uri shippingAdmin/wallet)
        // leaked into it as dead links: the sidebar builds hrefs with
        // admin_url(), i.e. admin/shippingAdmin — not a route anywhere (the
        // portal lives at /shippingAdmin and navigates via its own pages).
        // AdminMenuRebuildSeeder / the 2026_08_11 restructure migration remove
        // the stray rows; the admin-side management surface is the Agencies
        // leaf `shipping-super-admins`.

        $this->command->info('Shipping Super Admin role and permissions seeded successfully.');
    }
}
