<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Next-phase completion (Business Plan §§5-8 + must-add list):
 *  - POS gaps: table numbers, split-tender persistence, tendered/change,
 *    delivery workflow fields, campaign code on the bill.
 *  - Operations: partial refunds ledger, staff shifts, dining tables,
 *    discount campaigns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pos_orders', function (Blueprint $table) {
            $table->string('table_number', 20)->nullable()->after('notes')->index();
            $table->text('split_details')->nullable()->after('table_number');
            $table->decimal('tendered_amount', 10, 2)->default(0)->after('split_details');
            $table->decimal('change_amount', 10, 2)->default(0)->after('tendered_amount');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('change_amount');
            $table->string('refund_reason', 200)->nullable()->after('refunded_amount');
            $table->string('delivery_address')->nullable()->after('refund_reason');
            $table->string('delivery_agent', 80)->nullable()->after('delivery_address');
            $table->string('delivery_status', 20)->default('pending')->after('delivery_agent')->index();
            $table->string('campaign_code', 40)->nullable()->after('delivery_status')->index();
            $table->decimal('campaign_discount', 10, 2)->default(0)->after('campaign_code');
        });

        Schema::create('tbl_pos_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(table: 'tbl_pos_stores')->cascadeOnDelete();
            $table->foreignId('order_id')->index()->constrained(table: 'tbl_pos_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained(table: 'tbl_pos_users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('mode', 20)->default('cash')->index();
            $table->string('reason', 200);
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });

        Schema::create('tbl_pos_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(table: 'tbl_pos_stores')->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained(table: 'tbl_pos_users')->cascadeOnDelete();
            $table->timestamp('opened_at')->useCurrent()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->decimal('opening_cash', 10, 2)->default(0);
            $table->decimal('closing_cash', 10, 2)->nullable();
            $table->decimal('expected_cash', 10, 2)->nullable();
            $table->string('notes', 200)->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });

        Schema::create('tbl_pos_dining_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(table: 'tbl_pos_stores')->cascadeOnDelete();
            $table->string('table_number', 20)->index();
            $table->unsignedInteger('seats')->default(4);
            // available | occupied | reserved
            $table->string('status', 20)->default('available')->index();
            $table->foreignId('current_order_id')->nullable()->constrained(table: 'tbl_pos_orders')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'table_number']);
        });

        Schema::create('tbl_pos_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->index()->constrained(table: 'tbl_pos_stores')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 40)->nullable()->index();
            // percent | flat
            $table->string('type', 20)->default('percent')->index();
            $table->decimal('value', 10, 2)->default(0);
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_campaigns');
        Schema::dropIfExists('tbl_pos_dining_tables');
        Schema::dropIfExists('tbl_pos_shifts');
        Schema::dropIfExists('tbl_pos_refunds');

        Schema::table('tbl_pos_orders', function (Blueprint $table) {
            $table->dropColumn([
                'table_number', 'split_details', 'tendered_amount', 'change_amount',
                'refunded_amount', 'refund_reason', 'delivery_address', 'delivery_agent',
                'delivery_status', 'campaign_code', 'campaign_discount',
            ]);
        });
    }
};
