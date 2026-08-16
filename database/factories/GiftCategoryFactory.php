<?php

namespace Database\Factories;

use App\Models\GiftCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCategory>
 *
 * Added for Phase A perf tests. GiftCategory uses HasFactory but had no factory file.
 * GiftCategory::$casts casts `title` to array.
 */
class GiftCategoryFactory extends Factory
{
    protected $model = GiftCategory::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => $this->faker->word(), 'ar' => $this->faker->word()],
            'type' => 'normal',
            'sort' => $this->faker->numberBetween(1, 100),
        ];
    }
}
