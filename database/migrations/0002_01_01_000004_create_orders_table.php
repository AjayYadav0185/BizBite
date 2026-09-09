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
        Schema::create('tbl_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(
                table: 'tbl_stores'
            )->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained(
                table: 'tbl_users'
            )->nullOnDelete();
            $table->string('order_number')->index();
            // Indian market: money snapshot (subtotal/discount/tax/round-off/grand total),
            // order context (dine-in/counter/parcel), GST + idempotency for Flutter retries.
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('round_off', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('payment_mode', ['cash', 'upi', 'card', 'credit', 'split'])->default('cash');
            $table->enum('payment_status', ['paid', 'unpaid', 'partial'])->default('paid')->index();
            $table->enum('status', ['pending', 'preparing', 'ready', 'completed', 'cancelled'])->default('completed')->index();
            $table->enum('order_type', ['dine_in', 'takeaway', 'parcel', 'delivery'])->default('takeaway')->index();
            $table->string('upi_ref', 60)->nullable()->index();
            $table->string('invoice_number', 40)->nullable()->index();
            $table->string('customer_name', 80)->nullable();
            $table->string('customer_phone', 15)->nullable()->index();
            $table->string('idempotency_key', 64)->nullable()->unique();
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
        Schema::dropIfExists('tbl_orders');
    }
};