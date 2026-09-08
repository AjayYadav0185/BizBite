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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(
                table: 'stores'
            )->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained(
                table: 'users'
            )->nullOnDelete();
            $table->string('order_number')->index();
            $table->decimal('total_amount', 8, 2)->default(0);
            $table->enum('payment_mode', ['cash', 'upi', 'card'])->default('cash');
            $table->enum('status', ['completed', 'cancelled'])->default('completed')->index();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->unique(['store_id', 'order_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};