<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer Wallet System — schema.
     *
     * Conventions: every business table uses the `tbl_pos_` prefix.
     * - Adds `wallet_balance` (decimal, default 200 sign-up bonus) to users.
     * - Creates `tbl_pos_wallet_transactions` ledger (signed amount, credit/debit).
     */
    public function up(): void
    {
        Schema::table('tbl_pos_users', function (Blueprint $table) {
            $table->decimal('wallet_balance', 10, 2)->default(200)->after('remember_token');
        });

        // Backfill pre-existing rows (fresh installs get the column default).
        DB::table('tbl_pos_users')->whereNull('wallet_balance')->orWhere('wallet_balance', 0)->update(['wallet_balance' => 200]);

        Schema::create('tbl_pos_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained(table: 'tbl_pos_users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['credit', 'debit'])->index();
            $table->string('description', 120);
            $table->string('reference_id', 100)->nullable()->index();
            $table->string('razorpay_order_id', 100)->nullable()->index();
            $table->decimal('balance_after', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_wallet_transactions');

        Schema::table('tbl_pos_users', function (Blueprint $table) {
            $table->dropColumn('wallet_balance');
        });
    }
};
