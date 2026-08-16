<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Modules\Moment\Entities\Moment;


class PercentageBoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $percentages = [0, 20, 40, 60, 80, 95, 100];

        foreach ($percentages as $percentage) {
            \Modules\RoomBoom\Entities\BoomPercentage::firstOrCreate([
                'percentage' => $percentage
            ]);
        }
    }
}
