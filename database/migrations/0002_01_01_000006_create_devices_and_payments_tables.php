<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Indian market Phase-2 support:
     *  - tbl_store_devices: one row per Flutter device (FCM push + offline queue bookkeeping).
     *  - tbl_payments: payment attempts per order (UPI-first India: UPI ref / txn id / QR payload).
     */
    public function up(): void
    {
        Schema::create('tbl_store_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(table: 'tbl_stores')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained(table: 'tbl_users')->nullOnDelete();
            $table->string('device_id', 100)->index();
            // android | ios | web | pos
            $table->string('platform', 20)->default('android')->index();
            $table->string('app_version', 20)->nullable();
            $table->text('fcm_token')->nullable();
            $table->timestamp('last_sync_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['store_id', 'device_id']);
        });

        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(table: 'tbl_stores')->cascadeOnDelete();
            $table->foreignId('order_id')->index()->constrained(table: 'tbl_orders')->cascadeOnDelete();
            // cash | upi | card | credit | split
            $table->string('mode', 20)->default('cash')->index();
            $table->decimal('amount', 10, 2)->default(0);
            // initiated | success | failed | refunded
            $table->string('status', 20)->default('success')->index();
            // UPI-first India fields.
            $table->string('upi_ref', 60)->nullable()->index();
            $table->string('upi_txn_id', 100)->nullable()->index();
            $table->string('vpa', 100)->nullable();
            $table->string('provider', 60)->nullable()->index();
            $table->text('provider_payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_payments');
        Schema::dropIfExists('tbl_store_devices');
    }
};
