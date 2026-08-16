<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ware;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\SpecialId\Entities\UserWare;

class specialidrequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for($i=0;$i<10;$i++){

            $user_id = User::inRandomOrder()->first()->id;
            $ware_id = Ware::inRandomOrder()->first()->id;
            UserWare::create([
                'disable' => 0,
                'user_id' => $user_id,
                'ware_id' => $ware_id
            ]);
        }

    }
}
