<?php

namespace Database\Seeders;

use App\Models\Bd;
use Modules\Country\Entities\SuperAdmin;use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultSuperAdminBdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSuperAdmin = SuperAdmin::where('default', 1)->where(function ($q) {
            $q->where('country_id', 0)
                ->orWhereNull('country_id');
        })->first();

        if (!$defaultSuperAdmin){
            try {
                $defaultSuperAdmin = SuperAdmin::create([
                    'username' => 'defaultSuperAdmin',
                    'password' => Hash::make('defaultSuperAdmin'),
                    'name' => 'default Super Admin',
                    'type' => 'country',
                    'default' => 1,
                    'country_id' => 0,
                ]);
            } catch (\Exception $e) {
                \Log::error('Error creating default SuperAdmin: ' . $e->getMessage());
            }
        }
        if ($defaultSuperAdmin && is_null($defaultSuperAdmin->country_id)) {
            try {
                $defaultSuperAdmin->update(['country_id' => 0]);
            } catch (\Exception $e) {
                \Log::error('Error updating default SuperAdmin: ' . $e->getMessage());
            }
        }

        $defaultBd = Bd::where('default', 1)->where(function ($q) {
            $q->where('country_id', 0)
                ->orWhereNull('country_id');
        })->first();
        if ($defaultBd){
            if (is_null($defaultBd->country_id)) {
                try {
                  $defaultBd->update(['country_id' => 0]);
                } catch (\Exception $e) {
                    \Log::error('Error updating default Bd: ' . $e->getMessage());
                }
            }

            try {
              $defaultBd->update(['parent_id' => $defaultSuperAdmin->id]);
            } catch (\Exception $e) {
                \Log::error('Error updating default Bd parent_id: ' . $e->getMessage());
            }

        }else {
            try {
              Bd::create([
                  'parent_id' => $defaultSuperAdmin->id,
                  'username' => 'defaultBd',
                  'password' => Hash::make('defaultBd'),
                  'name' => 'default Bd',
                  'type' => 'bd',
                  'default' => 1,
                  'country_id' => 0,
              ]);
            } catch (\Exception $e) {
                \Log::error('Error creating default Bd: ' . $e->getMessage());
            }

        }
    }
}
