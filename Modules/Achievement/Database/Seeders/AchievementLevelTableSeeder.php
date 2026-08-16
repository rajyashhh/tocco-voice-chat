<?php

namespace Modules\Achievement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Enums\TargetType;

class AchievementLevelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        AchievementLevel::query()->create([
            'achievement_id' => 1,
            'invalid_image' => '/test',
            'valid_image' => '/test',
            'target' => 10000000,
            'target_type' => TargetType::MONTHLY
                                          ]);

        AchievementLevel::query()->create([
                                              'achievement_id' => 1,
                                              'invalid_image' => '/test',
                                              'valid_image' => '/test',
                                              'target' => 20000000,
                                              'target_type' => TargetType::MONTHLY
                                          ]);
        AchievementLevel::query()->create([
                                              'achievement_id' => 1,
                                              'invalid_image' => '/test',
                                              'valid_image' => '/test',
                                              'target' => 50000000,
                                              'target_type' => TargetType::DEFAULT
                                          ]);

        AchievementLevel::query()->create([
                                              'achievement_id' => 1,
                                              'invalid_image' => '/test',
                                              'valid_image' => '/test',
                                              'target' => 100000000,
                                              'target_type' => TargetType::DEFAULT
                                          ]);
    }
}
