<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track Razorpay recharge intents so initiate -> verify is auditable and
     * replay-safe (one row per razorpay_order_id).
     */
    public function up(): void
    {
        Schema::create('tbl_pos_wallet_recharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained(table: 'tbl_pos_users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('razorpay_order_id', 100)->unique();
            $table->string('razorpay_payment_id', 100)->nullable()->index();
            $table->string('razorpay_signature', 255)->nullable();
            $table->enum('status', ['created', 'verified', 'failed'])->default('created')->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_wallet_recharges');
    }
};
