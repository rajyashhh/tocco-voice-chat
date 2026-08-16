<?php

namespace Modules\Achievement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Achievement\Entities\Achievement;
use Modules\Achievement\Enums\AchievementType;

class AchievementTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        Achievement::query()->create([
            'type' => AchievementType::RECHARGE_TARGET,
            'valid_image' => '/test',
            'invalid_image' => '/test2',
                                     ]);

        Achievement::query()->create([
                                         'type' => AchievementType::ROOM_TARGET,
                                         'valid_image' => '/test',
                                         'invalid_image' => '/test2',
                                     ]);

        Achievement::query()->create([
                                         'type' => AchievementType::GIFT_TARGET,
                                         'valid_image' => '/test',
                                         'invalid_image' => '/test2',
                                     ]);
    }
}
