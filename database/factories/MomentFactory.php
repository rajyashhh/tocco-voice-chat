<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Moment\Entities\Moment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class MomentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Moment::class;

    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('2020-01-01', 'now');

        return [
            'user_id' => rand(1,2),
            'description' => fake()->text(),
            'created_at' => $createdAt,
            'updated_at' => $this->faker->dateTimeBetween($createdAt, 'now')
        ];
    }
}
