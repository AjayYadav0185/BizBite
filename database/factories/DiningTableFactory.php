<?php

namespace Database\Factories;

use App\Models\DiningTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiningTable>
 */
class DiningTableFactory extends Factory
{
    protected $model = DiningTable::class;

    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'table_number' => 'T'.fake()->unique()->numberBetween(1, 99),
            'seats' => fake()->numberBetween(2, 8),
            'status' => DiningTable::STATUS_AVAILABLE,
        ];
    }
}
