<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{


    protected $model = Room::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numid' => $this->faker->unique()->numberBetween(1000, 9999),
             // Associate with a User
            'room_status' => $this->faker->numberBetween(1, 4),
            'room_name' => $this->faker->word(),
            'room_cover' => $this->faker->imageUrl(),
            'room_intro' => $this->faker->sentence(),
            'room_pass' => $this->faker->password(),
            'room_class' => $this->faker->numberBetween(1, 10),
            'room_type' => $this->faker->numberBetween(1, 10),
            'room_welcome' => $this->faker->sentence(),
          // 'uid' => User::factory(), 
            'room_visitor' => $this->faker->numberBetween(0, 50),
            'room_speak' => $this->faker->sentence(),
            'room_sound' => $this->faker->sentence(),
            'room_black' => $this->faker->sentence(),
            'ranking' => $this->faker->numberBetween(1, 100),
            'is_popular' => $this->faker->boolean(),
            'secret_chat' => $this->faker->boolean(),
            'is_top' => $this->faker->boolean(),
            'sort' => $this->faker->numberBetween(1, 10),
            'room_background' => $this->faker->imageUrl(),
            
            'is_afk' => $this->faker->boolean(),
            'hot' => $this->faker->boolean(),
            'room_judge' => $this->faker->sentence(),
            'microphone' => $this->faker->sentence(),
            'is_prohibit_sound' => $this->faker->boolean(),
            'is_recommended' => $this->faker->boolean(),
            'play_num' => $this->faker->boolean(),
            'free_mic' => $this->faker->boolean(),
            'total_game_coins' => $this->faker->numberBetween(0, 50)
        ];

    }
}
