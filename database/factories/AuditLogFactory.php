<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'user_id' => \App\Models\User::factory(),
            'user_name' => fake()->name(),
            'action' => fake()->randomElement([
                AuditLog::ACTION_PRICE_UPDATED,
                AuditLog::ACTION_ORDER_DISCOUNT,
                AuditLog::ACTION_CREDIT_BILL,
                AuditLog::ACTION_STORE_SETTINGS,
            ]),
            'entity_type' => 'food_item',
            'entity_id' => fake()->numberBetween(1, 60),
            'entity_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'old_values' => null,
            'new_values' => null,
            'amount' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
