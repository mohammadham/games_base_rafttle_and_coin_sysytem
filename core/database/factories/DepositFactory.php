<?php

namespace Database\Factories;

use App\Constants\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepositFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'method_code' => 'zarinpal',
            'amount' => $this->faker->numberBetween(1000, 100000),
            'method_currency' => 'IRT',
            'charge' => 0,
            'rate' => 1,
            'final_amount' => function (array $attributes) {
                return $attributes['amount'];
            },
            'status' => Status::PAYMENT_INITIATE,
            'trx' => getTrx(),
        ];
    }
}
