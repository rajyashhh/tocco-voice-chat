<?php

namespace Database\Factories;

use App\Models\Gift;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiftFactory extends Factory
{
    protected $model = Gift::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' Gift',
            'type' => 6,
            'price' => 1000,
            'enable' => 1,
            'img' => 'test-gift.png',
            'show_img' => 'test-gift.png',
        ];
    }
}
