<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'user_id' => \App\Models\User::factory(),
            'opened_at' => now()->subHours(2),
            'opening_cash' => fake()->randomFloat(2, 100, 2000),
            'status' => Shift::STATUS_OPEN,
        ];
    }
}
