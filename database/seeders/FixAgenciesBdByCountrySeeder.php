<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Bd;
use Illuminate\Database\Seeder;

class FixAgenciesBdByCountrySeeder extends Seeder
{
    public function run(): void
    {
        Agency::with('bd')
            ->whereNotNull('country_id')
            ->chunk(200, function ($agencies) {
                foreach ($agencies as $agency) {
                    $agencyCountry = $agency->country_id;

                    if ($agency->bd && $agency->bd->country_id != $agencyCountry) {
                        $defaultBd = Bd::where('country_id', $agencyCountry)
                            ->where('default', 1)
                            ->first();

                        if ($defaultBd) {
                            $agency->bd_id = $defaultBd->id;
                            $agency->save();
                        } else {
                            $firstBd = Bd::where('country_id', $agencyCountry)->first();

                            if ($firstBd) {
                                $firstBd->default = 1;
                                $firstBd->save();

                                $agency->bd_id = $firstBd->id;
                                $agency->save();
                            } else {
                              
                            }
                        }
                    }
                }
            });
    }
}
