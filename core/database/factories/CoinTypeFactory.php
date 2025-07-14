<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CoinTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'code' => strtoupper($this->faker->unique()->word),
            'symbol' => strtoupper($this->faker->lexify('??')),
            'is_base_coin' => false,
            'base_coin_value_multiplier' => $this->faker->numberBetween(1, 100),
            'status' => 1,
        ];
    }
}
