<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'type' => Campaign::TYPE_PERCENT,
            'value' => 10,
            'min_order_amount' => 0,
            'is_active' => true,
        ];
    }
}
