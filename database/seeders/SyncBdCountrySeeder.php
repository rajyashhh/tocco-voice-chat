<?php

namespace Database\Seeders;

use App\Models\Bd;
use Modules\Country\Entities\SuperAdmin;
use Illuminate\Database\Seeder;

class SyncBdCountrySeeder extends Seeder
{
    public function run(): void
    {
        Bd::with('appUser')
            ->where('default', '!=', 1)
            ->where('country_id', '!=', 0)
            ->whereNull('country_id')
            ->chunk(100, function ($bds) {
                foreach ($bds as $bd) {
                    if ($bd->parent && $bd->parent->country_id && !$bd->parent->default) {
                        $bd->country_id = $bd->parent->country_id;
                    } elseif ($bd->appUser && $bd->appUser->country_id) {
                        $bd->country_id = $bd->appUser->country_id;
                    }

                    if ($bd->country_id) {
                        $bd->save();
                    }
                }
            });

        $superAdmins = SuperAdmin::all()->keyBy('country_id');

        $defaultSuperAdmin = $superAdmins->first(function ($admin) {
            return $admin->default == 1 && $admin->country_id == 0;
        });

        Bd::whereNull('parent_id')
            ->where('default', '!=', 1)
            ->where(function ($q) {
                $q->whereNull('country_id')
                    ->orWhere('country_id', '!=', 0);
            })
            ->chunk(100, function ($bds) use ($superAdmins, $defaultSuperAdmin){
                foreach ($bds as $bd) {
                    $superAdmin = $superAdmins->get($bd->country_id) ?? $defaultSuperAdmin;

                    if ($superAdmin) {
                        $bd->parent_id = $superAdmin->id;
                        $bd->save();
                    }
                }
            });
    }
}
