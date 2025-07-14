<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class LotteryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'competition_id' => Competition::factory(),
            'product_id' => Product::factory(),
            'name' => $this->faker->sentence,
            'price' => $this->faker->numberBetween(10, 100),
            'draw_date' => now()->addDays(7),
            'status' => 1,
        ];
    }
}
