<?php

namespace Database\Factories;

use App\Models\Enums\OrderStatus;
use App\Models\Enums\PaymentMode;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . fake()->unique()->numberBased(10000),
            'total_amount' => fake()->randomFloat(2, 5, 500),
            'payment_mode' => fake()->randomElement(PaymentMode::cases()),
            'status' => fake()->randomElement(OrderStatus::cases()),
        ];
    }
}
