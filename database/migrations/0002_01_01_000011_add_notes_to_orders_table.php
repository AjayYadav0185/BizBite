<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MVP scope (§6): a cashier must be able to attach free-text notes to a bill
 * (e.g. "no onion", "extra spicy", table/parcel remark). The existing order
 * header already stores subtotal / discount / tax / total, so only the note
 * column is missing.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_pos_orders', function (Blueprint $table) {
            $table->string('notes', 200)->nullable()->after('customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_pos_orders', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
