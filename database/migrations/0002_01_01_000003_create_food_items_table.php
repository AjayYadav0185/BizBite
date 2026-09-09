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
        Schema::create('tbl_food_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(
                table: 'tbl_stores'
            )->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained(
                table: 'tbl_categories'
            )->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_available')->default(true)->index();
            // Indian market: veg/non-veg/egg marker, GST slab, stable ordering + offline sync.
            $table->enum('food_type', ['veg', 'non_veg', 'egg'])->default('veg')->index();
            $table->unsignedTinyInteger('gst_rate')->default(5);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->uuid('uuid')->nullable()->unique();
            $table->timestamps();

            $table->index(['store_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_food_items');
    }
};