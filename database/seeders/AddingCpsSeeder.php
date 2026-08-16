<?php

namespace Database\Seeders;

use App\Models\Cp;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddingCpsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        for($i =0; $i < 10; $i++){
            $userOne = User::inRandomOrder()->first();
            $userTwo = User::inRandomOrder()->first();

            if($userOne->id == $userTwo->id)
                continue;

                $find = Cp::where('user_one_id', $userOne->id)->where('user_two_id', $userTwo->id)->exists();
                $find2 = Cp::where('user_one_id', $userTwo->id)->where('user_two_id', $userOne->id)->exists();

            if($find || $find2)
                continue;

            Cp::create([
                'cp_relation_id' => rand(1,3),
                'user_one_id' => $userOne->id,
                'user_two_id' => $userTwo->id,
                'status' => 1,
                'di' => 0,
                'level_id' => rand(1,5),
                'price' => 100,
            ]);
        }
    }
}
