<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Flutter offline-first support: queued client mutations (create-order, settle-payment)
     * are stored with the client's idempotency key and replayed exactly once.
     */
    public function up(): void
    {
        Schema::create('tbl_pos_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(table: 'tbl_pos_stores')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained(table: 'tbl_pos_users')->nullOnDelete();
            $table->string('device_id', 100)->nullable()->index();
            // create_order | settle_payment | upsert_menu
            $table->string('action', 40)->index();
            $table->string('idempotency_key', 64)->unique();
            $table->longText('payload');
            // queued | processing | applied | failed
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('applied_at')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_sync_queue');
    }
};
