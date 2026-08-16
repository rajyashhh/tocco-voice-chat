<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Family;
use App\Models\Profile;
use App\Models\FamilyUser;
use Illuminate\Database\Seeder;

class FamilyCountryUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $families = Family::with('owner')->get();

        foreach ($families as $family) {
            $family->country_id =   @$family->owner?->country_id ?? null;
            $family->save();
        }
    }
}
