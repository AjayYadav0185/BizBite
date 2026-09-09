<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained(
                table: 'tbl_orders'
            )->cascadeOnDelete();
            // Snapshot of the sold item: optional link back to the live menu + full tax split for GST bills.
            $table->foreignId('food_item_id')->nullable()->constrained(table: 'tbl_food_items')->nullOnDelete();
            $table->string('food_item_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->unsignedTinyInteger('gst_rate')->default(5);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_order_items');
    }
};