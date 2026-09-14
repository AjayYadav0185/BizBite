<?php

namespace Database\Factories;

use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'order_id' => \App\Models\Order::factory(),
            'user_id' => \App\Models\User::factory(),
            'amount' => fake()->randomFloat(2, 5, 200),
            'mode' => 'cash',
            'reason' => fake()->sentence(),
        ];
    }
}
