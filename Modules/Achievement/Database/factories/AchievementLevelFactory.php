<?php

namespace Modules\Achievement\Database\factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achievement\Enums\TargetType;

/**
 * @extends >
 */
class AchievementLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition()
    {

        $targetType = $this->faker->randomElement(TargetType::cases());
        return [
            // 'user_id' => $this->faker->user_id,
            'gift_id' => $targetType,
            'target' => 3000,
            'target_type' => $targetType,
            'valid_image' => 'images/valid.png',
            'invalid_image' => 'images/invalid.png',
            'achievement_id' => 1
            // Add more fields as per your requirements
        ];
    }


}
