<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\FoodItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Enums\UserRole;
use App\Services\Audit;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a North Indian food outlet (Apna Zaika) with Indian staff,
     * North Indian categories and INR-priced menu items, plus a few
     * illustrative audit-log rows so the owner's Audit Logs tab is not
     * empty on a fresh install (price change, discount, credit bill,
     * availability toggle, settings edit, staff logins).
     *
     * We create the store first and pass its ID explicitly to every
     * child create call. This prevents the factory definitions' default
     * `Store::factory()` calls from spawning duplicate stores.
     */
    public function run(): void
    {
        $store = Store::factory()->create([
            'name' => 'Apna Zaika - North Indian Food Outlet',
            'phone' => '+91 98110 45678',
            'alternate_phone' => '+91 11 2745 8899',
            'address' => 'Shop No. 12, Main Market, Model Town, New Delhi 110009',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'pincode' => '110009',
            'gstin' => '07ABCDE1234F1Z5',
            'fssai_license' => '10012043001234',
            'upi_vpa' => 'apnazaika@upi',
            'currency' => 'INR',
            'default_gst_rate' => 5.00,
            'is_gst_enabled' => false,
            'is_active' => true,
            'print_header' => 'APNA ZAIKA - SWAD DESI TADKE KA',
            'print_footer' => 'Shukriya! Phir Padhariye! Visit Again!',
        ]);

        User::factory()->create([
            'store_id' => $store->id,
            'name' => 'Rajesh Sharma',
            'email' => 'admin@mail.com',
            'phone' => '+91 98110 45678',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'store_id' => $store->id,
            'name' => 'Priya Verma',
            'email' => 'cashier@mail.com',
            'phone' => '+91 98990 12345',
            'password' => 'password',
            'role' => UserRole::Cashier,
        ]);

        /**
         * Category => [[item name, price in INR, food_type], ...]
         * Prices are typical North Indian dhaba / QSR rates.
         */
        $menu = [
            'Tandoor & Tikkas' => [
                ['Paneer Tikka Angara', 220],
                ['Amritsari Paneer Tikka', 210],
                ['Soya Chaap Tandoori', 180],
                ['Soya Chaap Malai', 200],
                ['Hara Bhara Kebab (6 Pc)', 140],
                ['Dahi Ke Kebab (6 Pc)', 170],
                ['Tandoori Mushroom', 200],
                ['Tandoori Chicken Half (4 Pc)', 250],
                ['Chicken Malai Tikka', 270],
            ],
            'Veg Main Course' => [
                ['Paneer Butter Masala', 230],
                ['Shahi Paneer', 220],
                ['Kadhai Paneer', 230],
                ['Palak Paneer', 200],
                ['Mix Veg Dhaba Style', 170],
                ['Aloo Gobhi Masala', 150],
                ['Chole Amritsari', 160],
                ['Rajma Dhaba Style', 160],
            ],
            'Non-Veg Main Course' => [
                ['Butter Chicken', 280],
                ['Kadhai Chicken', 270],
                ['Dhaba Chicken Curry', 250],
                ['Egg Curry (4 Pc)', 180],
                ['Mutton Rogan Josh', 340],
                ['Keema Matar', 300],
            ],
            'Dal, Breads & Thali' => [
                ['Dal Makhani', 190],
                ['Dal Tadka Fry', 160],
                ['Tandoori Roti', 15],
                ['Butter Tandoori Roti', 20],
                ['Plain Naan', 30],
                ['Butter Naan', 40],
                ['Garlic Naan', 50],
                ['Laccha Paratha', 45],
                ['Chole Bhature (2 Pc)', 120],
                ['Rajma Chawal Combo', 140],
                ['Veg Thali (Dal, Sabzi, 3 Roti, Rice, Salad)', 180],
                ['Special Maharaja Thali', 250],
            ],
            'Biryani, Pulao & Rice' => [
                ['Veg Dum Biryani with Raita', 160],
                ['Chicken Dum Biryani', 220],
                ['Mutton Biryani', 320],
                ['Egg Biryani', 180],
                ['Veg Pulao with Raita', 140],
                ['Jeera Rice', 120],
                ['Steamed Rice', 100],
                ['Chicken Fried Rice Desi Style', 190],
            ],
            'Chaat & Street Snacks' => [
                ['Aloo Tikki Chole', 80],
                ['Pani Puri / Golgappe (8 Pc)', 60],
                ['Papdi Chaat', 70],
                ['Dahi Bhalla Chaat', 90],
                ['Punjabi Samosa (2 Pc)', 40],
                ['Bread Pakora (2 Pc)', 50],
                ['Paneer Pakora', 120],
                ['Masala French Fries', 99],
            ],
            'Desserts & Beverages' => [
                ['Gulab Jamun (4 Pc)', 60],
                ['Gajar Ka Halwa', 100],
                ['Rasmalai (2 Pc)', 80],
                ['Kulfi Falooda', 120],
                ['Punjabi Sweet Lassi', 80],
                ['Mango Lassi', 100],
                ['Masala Chaas', 50],
                ['Kulhad Masala Chai', 30],
                ['Cold Coffee', 110],
            ],
        ];

        $sortOrder = 0;
        foreach ($menu as $categoryName => $items) {
            $category = Category::create([
                'store_id' => $store->id,
                'name' => $categoryName,
                'is_active' => true,
                'sort_order' => $sortOrder++,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
            ]);

            $itemSort = 0;
            foreach ($items as $itemRow) {
                [$itemName, $price] = $itemRow;
                $foodType = $itemRow[2] ?? 'veg';
                FoodItem::create([
                    'store_id' => $store->id,
                    'category_id' => $category->id,
                    'name' => $itemName,
                    'price' => $price,
                    'is_available' => true,
                    'food_type' => $foodType,
                    'gst_rate' => 5,
                    'sort_order' => $itemSort++,
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                ]);
            }
        }
    }
}

