<?php
namespace Modules\Country\Database\Seeders;

use App\Models\AdminRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuperAdminRoleSeeder extends Seeder
{
    public function run()
    {
        $role = Role::where('slug', 'country')->first();

        if (!$role) {
            $role = Role::create([
                'name' => 'country',
                'slug' => 'country',
                'desc_en' => 'Full system access role',
                'desc_ar' => 'صلاحيات كاملة للنظام',
                'image' => null,
                'admin_id' => 1,
                'type' => 'system',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $permissions = Permission::whereHas('permissionTypes', function ($q) {
                $q->where('type', 'country');
            })->pluck('id')->toArray();

            $role->permissions()->sync($permissions);

            echo "✅";
        } else {


            $permissions = Permission::whereHas('permissionTypes', function ($q) {
                $q->where('type', 'country');
            })->pluck('id')->toArray();

            $role->permissions()->sync($permissions);
            echo "ℹ️ ";
        }




        $this->createOrUpdateRole('region', [
            'desc_en' => 'Regional management access role',
            'desc_ar' => 'صلاحيات إدارة المناطق',
        ]);
    }

    /**
     * Create or update a role and sync its permissions by type
     */
    protected function createOrUpdateRole(string $slug, array $descriptions)
    {
        $role = Role::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $slug,
                'desc_en' => $descriptions['desc_en'] ?? null,
                'desc_ar' => $descriptions['desc_ar'] ?? null,
                'image' => null,
                'admin_id' => 1,
                'type' => 'system',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        // Match permissions by type (same as slug)
        $permissions = Permission::whereHas('permissionTypes', function ($q) use ($slug) {
            $q->where('type', $slug);
        })->pluck('id')->toArray();

        $role->permissions()->sync($permissions);

        \App\Models\Admin::flushAllCachedPermissions();

        echo $role->wasRecentlyCreated
            ? "✅ Created role: {$slug}\n"
            : "ℹ️ Updated role: {$slug}\n";
    }
}
