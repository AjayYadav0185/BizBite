<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Priority feature (§5): stock control.
 *
 * `stock_quantity` is nullable on purpose: a NULL means the owner does NOT
 * track stock for that item (unlimited / made-to-order), so existing menu rows
 * keep selling exactly as before. A numeric value turns on counting, low-stock
 * alerting and oversell protection for that item.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_pos_food_items', function (Blueprint $table) {
            $table->integer('stock_quantity')->nullable()->after('is_available');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_pos_food_items', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'low_stock_threshold']);
        });
    }
};