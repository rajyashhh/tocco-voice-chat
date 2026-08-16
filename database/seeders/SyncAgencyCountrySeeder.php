<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Bd;
use Illuminate\Database\Seeder;

class SyncAgencyCountrySeeder extends Seeder
{
    public function run(): void
    {
        Agency::with('owner')
            ->whereNull('country_id')
            ->chunk(100, function ($agencies) {
                foreach ($agencies as $agency) {
                    if ($agency->bd && $agency->bd->country_id) {
                        $agency->country_id = $agency->bd->country_id;
                    }
                    if ($agency->owner && $agency->owner->country_id) {
                        $agency->country_id = $agency->owner->country_id;
                    }

                    if ($agency->country_id) {
                        try {
                            $agency->save();
                        }catch (\Exception $e){}
                    }
                }
            });

        $bds = Bd::where('default', 1)->whereNotNull('country_id')->get()->keyBy('country_id');

        $defaultBd = $bds->first(function ($bd) {
            return $bd->country_id == 0;
        });

        Agency::where(function ($q){
            $q->whereNull('bd_id')->orWhere('bd_id', 0);
        })
//            ->whereNotNull('country_id')
            ->chunk(100, function ($agencies) use ($bds, $defaultBd){
                foreach ($agencies as $agency) {
                    $bd = $bds->get($agency->country_id) ?? $defaultBd;

                    if ($bd) {
                        $agency->bd_id = $bd->id;
                        $agency->save();
                    }
                }
            });
    }
}
