<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Owner-visible audit trail: every price change, menu mutation, discount,
     * cancellation, credit bill, settings change and staff login is recorded
     * here with WHO did WHAT, WHEN, and the OLD vs NEW values.
     * Only admins can read this table (gate `view-audit-logs` + no API route).
     */
    public function up(): void
    {
        Schema::create('tbl_pos_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(table: 'tbl_pos_stores')->cascadeOnDelete();
            // Staff member who performed the action (null = system).
            $table->foreignId('user_id')->nullable()->constrained(table: 'tbl_pos_users')->nullOnDelete();
            $table->string('user_name', 100)->nullable();
            // price_updated | item_created | item_deleted | item_availability |
            // category_created | category_updated | category_deleted |
            // order_discount | order_cancelled | credit_bill | payment_settled |
            // store_settings | staff_login | staff_created | staff_deactivated
            $table->string('action', 40)->index();
            // food_item | category | order | store | user | session
            $table->string('entity_type', 20)->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('entity_name', 120)->nullable();
            $table->text('description');
            // JSON snapshots: ['price' => ['old' => '200', 'new' => '220']]
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['store_id', 'action']);
            $table->index(['store_id', 'created_at']);
            $table->index(['store_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_audit_logs');
    }
};
