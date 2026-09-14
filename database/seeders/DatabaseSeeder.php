<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\FoodItem;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Models\Enums\UserRole;
use App\Services\Audit;
use App\Services\OrderService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a North Indian food outlet (Apna Zaika) with Indian staff,
     * North Indian categories and INR-priced menu items, plus data for
     * every feature the migrations introduce:
     *
     *  - wallets        : sign-up bonus (200) + ledger rows per user
     *  - stock control  : tracked items (incl. one low-stock item)
     *  - dining tables  : T1..T8 with seats / statuses
     *  - campaigns      : live percent, live flat and an expired one
     *  - shifts         : a closed shift (admin) and an open one (cashier)
     *  - sample orders  : placed through OrderService so bill numbering,
     *                     GST math, order_items snapshots, payments, the
     *                     1% wallet debit and audit logs are all realistic
     *  - audit logs     : price change, availability toggle, settings edit,
     *                     staff logins (order/credit-bill logs come from
     *                     OrderService itself)
     *
     * We create the store first and pass its ID explicitly to every
     * child create call. This prevents the factory definitions' default
     * `Store::factory()` calls from spawning duplicate stores.
     */
    public function run(): void
    {
        $store = Store::factory()->create([
            'name' => 'Apna Zaika',
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

        $admin = User::factory()->create([
            'store_id' => $store->id,
            'name' => 'Rajesh Sharma',
            'email' => 'admin@gmail.com',
            'phone' => '+91 98110 45678',
            'password' => 'p',
            'role' => UserRole::Admin,
        ]);

        $cashier = User::factory()->create([
            'store_id' => $store->id,
            'name' => 'Priya Verma',
            'email' => 'cashier@gmail.com',
            'phone' => '+91 98990 12345',
            'password' => 'p',
            'role' => UserRole::Cashier,
        ]);

        // The store's owner is the admin (tbl_pos_stores.owner_user_id).
        $store->forceFill(['owner_user_id' => $admin->id])->save();

        // Wallet: 200-point sign-up bonus + a 'Sign-up bonus' ledger row each.
        $wallet = app(WalletService::class);
        $wallet->grantSignupBonus($admin);
        $wallet->grantSignupBonus($cashier);

        // Staff logins so the owner's Audit Logs tab shows logins too.
        Audit::record($admin, 'staff_login', 'Admin logged in (seed data).');
        Audit::record($cashier, 'staff_login', 'Cashier logged in (seed data).');

        /**
         * Category => [[item name, price in INR, food_type, stock_quantity], ...]
         * Prices are typical North Indian dhaba / QSR rates.
         * food_type: 'veg' (default) | 'non_veg' | 'egg'.
         * stock_quantity: null = not tracked (unlimited / made to order).
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
                ['Tandoori Chicken Half (4 Pc)', 250, 'non_veg'],
                ['Chicken Malai Tikka', 270, 'non_veg'],
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
                ['Butter Chicken', 280, 'non_veg'],
                ['Kadhai Chicken', 270, 'non_veg'],
                ['Dhaba Chicken Curry', 250, 'non_veg'],
                ['Egg Curry (4 Pc)', 180, 'egg'],
                ['Mutton Rogan Josh', 340, 'non_veg'],
                ['Keema Matar', 300, 'non_veg'],
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
                ['Chole Bhature (2 Pc)', 120, 'veg', 30],
                ['Rajma Chawal Combo', 140],
                ['Veg Thali (Dal, Sabzi, 3 Roti, Rice, Salad)', 180],
                ['Special Maharaja Thali', 250],
            ],
            'Biryani, Pulao & Rice' => [
                ['Veg Dum Biryani with Raita', 160],
                ['Chicken Dum Biryani', 220, 'non_veg'],
                ['Mutton Biryani', 320, 'non_veg'],
                ['Egg Biryani', 180, 'egg'],
                ['Veg Pulao with Raita', 140],
                ['Jeera Rice', 120],
                ['Steamed Rice', 100],
                ['Chicken Fried Rice Desi Style', 190, 'non_veg'],
            ],
            'Chaat & Street Snacks' => [
                ['Aloo Tikki Chole', 80],
                ['Pani Puri / Golgappe (8 Pc)', 60, 'veg', 50],
                ['Papdi Chaat', 70],
                ['Dahi Bhalla Chaat', 90],
                ['Punjabi Samosa (2 Pc)', 40, 'veg', 60],
                ['Bread Pakora (2 Pc)', 50],
                ['Paneer Pakora', 120],
                ['Masala French Fries', 99],
            ],
            'Desserts & Beverages' => [
                ['Gulab Jamun (4 Pc)', 60, 'veg', 40],
                ['Gajar Ka Halwa', 100],
                ['Rasmalai (2 Pc)', 80],
                ['Kulfi Falooda', 120],
                ['Punjabi Sweet Lassi', 80],
                ['Mango Lassi', 100, 'veg', 25],
                ['Masala Chaas', 50, 'veg', 20],
                ['Kulhad Masala Chai', 30, 'veg', 4],
                ['Cold Coffee', 110],
            ],
        ];

        /** @var array<string, FoodItem> $menuByName Lookup for the sample orders below. */
        $menuByName = [];

        $sortOrder = 0;
        foreach ($menu as $categoryName => $items) {
            $category = Category::create([
                'store_id' => $store->id,
                'name' => $categoryName,
                'is_active' => true,
                'sort_order' => $sortOrder++,
                'uuid' => (string) Str::uuid(),
            ]);

            $itemSort = 0;
            foreach ($items as $itemRow) {
                [$itemName, $price] = $itemRow;
                $foodType = $itemRow[2] ?? 'veg';
                $stockQuantity = $itemRow[3] ?? null;

                $menuByName[$itemName] = FoodItem::create([
                    'store_id' => $store->id,
                    'category_id' => $category->id,
                    'name' => $itemName,
                    'price' => $price,
                    'is_available' => true,
                    'stock_quantity' => $stockQuantity,
                    'low_stock_threshold' => 5,
                    'food_type' => $foodType,
                    'gst_rate' => 5,
                    'sort_order' => $itemSort++,
                    'uuid' => (string) Str::uuid(),
                ]);
            }
        }

        // Audit Logs tab: a price change and an availability toggle.
        Audit::record(
            $admin,
            'price_updated',
            'Changed price of Masala French Fries from ₹89 to ₹99.',
            'food_item',
            $menuByName['Masala French Fries']->id,
            'Masala French Fries',
            ['price' => '89.00'],
            ['price' => '99.00'],
        );

        $menuByName['Kulfi Falooda']->update(['is_available' => false]);
        Audit::record(
            $admin,
            'item_availability',
            'Marked Kulfi Falooda as unavailable for the evening.',
            'food_item',
            $menuByName['Kulfi Falooda']->id,
            'Kulfi Falooda',
            ['is_available' => true],
            ['is_available' => false],
        );

        // Dining tables (Phase 3: table management).
        $diningTables = collect([
            ['T1', 2], ['T2', 2], ['T3', 4], ['T4', 4],
            ['T5', 4], ['T6', 4], ['T7', 6], ['T8', 6],
        ])->mapWithKeys(fn (array $t): array => [
            $t[0] => DiningTable::create([
                'store_id' => $store->id,
                'table_number' => $t[0],
                'seats' => $t[1],
                'status' => DiningTable::STATUS_AVAILABLE,
            ]),
        ]);
        $diningTables['T3']->update(['status' => DiningTable::STATUS_RESERVED]);

        // Discount / loyalty campaigns.
        Campaign::create([
            'store_id' => $store->id,
            'name' => 'Festive Season 10% Off',
            'code' => 'FESTIVE10',
            'type' => Campaign::TYPE_PERCENT,
            'value' => 10,
            'min_order_amount' => 300,
            'is_active' => true,
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->addDays(30)->endOfDay(),
        ]);

        Campaign::create([
            'store_id' => $store->id,
            'name' => '₹50 Off Above ₹400',
            'code' => 'FLAT50',
            'type' => Campaign::TYPE_FLAT,
            'value' => 50,
            'min_order_amount' => 400,
            'is_active' => true,
            'starts_at' => now()->startOfDay(),
        ]);

        Campaign::create([
            'store_id' => $store->id,
            'name' => 'Summer Special 20% (Expired)',
            'code' => 'SUMMER20',
            'type' => Campaign::TYPE_PERCENT,
            'value' => 20,
            'min_order_amount' => 200,
            'is_active' => false,
            'starts_at' => now()->subMonths(2)->startOfDay(),
            'ends_at' => now()->subMonth()->endOfDay(),
        ]);

        // Staff shifts: yesterday's closed shift + the cashier's live one.
        Shift::create([
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'opened_at' => now()->subDay()->setTime(10, 0),
            'closed_at' => now()->subDay()->setTime(22, 30),
            'opening_cash' => 2000,
            'closing_cash' => 8450,
            'expected_cash' => 8460,
            'notes' => 'Day closed, ₹10 short (round-off).',
            'status' => Shift::STATUS_CLOSED,
        ]);

        Shift::create([
            'store_id' => $store->id,
            'user_id' => $cashier->id,
            'opened_at' => now()->setTime(10, 0),
            'opening_cash' => 2000,
            'status' => Shift::STATUS_OPEN,
        ]);

        // Store settings edit for the audit trail.
        Audit::record(
            $admin,
            'store_settings',
            'Updated GST settings: enabled GST on bills.',
            'store',
            $store->id,
            $store->name,
            ['is_gst_enabled' => false],
            ['is_gst_enabled' => true],
        );
        $store->update(['is_gst_enabled' => true]);

        // -----------------------------------------------------------------
        // Sample bills placed through the REAL checkout path (OrderService):
        // bill numbers, server-side pricing, order_items snapshots, payments,
        // the 1% wallet debit, stock decrement and audit rows all behave
        // exactly like production.
        // -----------------------------------------------------------------
        $orderService = app(OrderService::class);

        // 1) Dine-in cash bill on table T1 (marks the table occupied).
        $dineIn = $orderService->place($cashier, [
            'items' => [
                ['food_item_id' => $menuByName['Butter Chicken']->id, 'quantity' => 1],
                ['food_item_id' => $menuByName['Butter Naan']->id, 'quantity' => 4],
                ['food_item_id' => $menuByName['Gulab Jamun (4 Pc)']->id, 'quantity' => 1],
            ],
            'payment_mode' => 'cash',
            'order_type' => 'dine_in',
            'table_number' => 'T1',
            'notes' => 'Less spicy, extra onion salad',
            'tendered_amount' => 500,
            'idempotency_key' => 'seed-dine-in-t1',
        ]);
        $diningTables['T1']->update([
            'status' => DiningTable::STATUS_OCCUPIED,
            'current_order_id' => $dineIn->order->id,
        ]);

        // 2) Parcel UPI bill with the FESTIVE10 campaign applied.
        $orderService->place($cashier, [
            'items' => [
                ['food_item_id' => $menuByName['Chicken Dum Biryani']->id, 'quantity' => 2],
                ['food_item_id' => $menuByName['Punjabi Sweet Lassi']->id, 'quantity' => 2],
            ],
            'payment_mode' => 'upi',
            'order_type' => 'parcel',
            'campaign_code' => 'FESTIVE10',
            'customer_name' => 'Amit Gupta',
            'customer_phone' => '9876543210',
            'upi_ref' => '402512345678',
            'idempotency_key' => 'seed-parcel-upi-1',
        ]);

        // 3) Delivery bill paid by card.
        $orderService->place($admin, [
            'items' => [
                ['food_item_id' => $menuByName['Paneer Butter Masala']->id, 'quantity' => 1],
                ['food_item_id' => $menuByName['Garlic Naan']->id, 'quantity' => 3],
                ['food_item_id' => $menuByName['Jeera Rice']->id, 'quantity' => 1],
            ],
            'payment_mode' => 'card',
            'order_type' => 'delivery',
            'delivery_address' => 'B-14, Kamla Nagar, New Delhi 110007',
            'delivery_agent' => 'Sunil Kumar',
            'delivery_status' => 'out_for_delivery',
            'customer_name' => 'Neha Singh',
            'customer_phone' => '9123456780',
            'idempotency_key' => 'seed-delivery-card-1',
        ]);

        // 4) Credit bill (unpaid) — shows up in the owner's Udhaar report.
        $orderService->place($cashier, [
            'items' => [
                ['food_item_id' => $menuByName['Special Maharaja Thali']->id, 'quantity' => 2],
                ['food_item_id' => $menuByName['Masala Chaas']->id, 'quantity' => 2],
            ],
            'payment_mode' => 'credit',
            'order_type' => 'takeaway',
            'customer_name' => 'Sharma Trading Co.',
            'customer_phone' => '9811223344',
            'notes' => 'Monthly corporate khata — settle by 30th',
            'idempotency_key' => 'seed-credit-khata-1',
        ]);
    }
}

