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
        Schema::create('food_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(
                table: 'stores'
            )->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained(
                table: 'categories'
            )->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 8, 2)->default(0);
            $table->boolean('is_available')->default(true)->index();
            $table->timestamps();

            $table->index(['store_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_items');
    }
};