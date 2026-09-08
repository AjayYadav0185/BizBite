<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FoodItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Enums\UserRole;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a demo store, two users
     * (admin + cashier), three categories and 5 food items each.
     *
     * We create the store first and pass its ID explicitly to every
     * child factory call. This prevents the factory definitions' default
     * `Store::factory()` calls from spawning duplicate stores in the
     * nested `has()` / `create()` chain.
     */
    public function run(): void
    {
        $store = Store::factory()->create([
            'name' => 'Demo Store',
            'phone' => '555-0100',
            'address' => '123 Main Street, Demo City',
            'print_header' => 'BIZBITE POS',
            'print_footer' => 'Thank you for your order!',
        ]);

        User::factory()->create([
            'store_id' => $store->id,
            'name' => 'Admin User',
            'email' => 'admin@bizbite.test',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'store_id' => $store->id,
            'name' => 'Cashier User',
            'email' => 'cashier@bizbite.test',
            'password' => 'password',
            'role' => UserRole::Cashier,
        ]);

        Category::factory(3)
            ->create([
                'store_id' => $store->id,
            ])
            ->each(function (Category $category) use ($store) {
                FoodItem::factory(5)->create([
                    'store_id' => $store->id,
                    'category_id' => $category->id,
                ]);
            });
    }
}
