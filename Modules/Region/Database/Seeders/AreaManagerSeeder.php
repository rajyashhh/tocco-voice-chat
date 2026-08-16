<?php

namespace Modules\Region\Database\Seeders;

use App\Models\Country;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Modules\Region\Entities\AreaManager;
use Modules\Region\Entities\Region;

class AreaManagerSeeder extends Seeder
{
    public function run(): void
    {

     if (AreaManager::where('default', 1)->exists()) {
        $this->command->info('المدير الإفتراضي موجود بالفعل، سيتم تخطي الأمر.');
        return;
        }
        $manager = AreaManager::create([
            'username' => 'default-area-manager',
            'name' => 'Default Area Manager',
            'password' => bcrypt('password123'),  
            'type' => 'region',
            'default' => 1,
            
        ]);
        $defaultRegion = Region::firstOrCreate(
            ['name' => 'Default Region for Default Manager', 'manager_id' => $manager->id]
        );

        $countriesWithoutRegion = Country::whereDoesntHave('regions')->get();

        foreach ($countriesWithoutRegion as $country) {
            $country->regions()->attach($defaultRegion->id);
        }
    }
}
