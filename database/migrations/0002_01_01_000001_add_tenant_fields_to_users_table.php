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
        // Rework the default users table into the multi-tenant shape while keeping
        // the columns the framework's session / password-reset services expect.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->index()->constrained(
                table: 'stores'
            )->cascadeOnDelete();
            $table->enum('role', ['admin', 'cashier'])->default('cashier')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn('role');
        });
    }
};