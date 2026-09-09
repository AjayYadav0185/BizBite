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
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->index()->constrained(
                table: 'tbl_stores'
            )->cascadeOnDelete();
            $table->enum('role', ['admin', 'cashier'])->default('cashier')->index();
            // Indian market: 10-digit mobile (stored with +91 optional), active flag + login tracking.
            $table->string('phone', 15)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('role')->index();
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn(['role', 'phone', 'is_active', 'last_login_at']);
        });
    }
};