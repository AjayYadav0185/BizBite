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
        Schema::create('tbl_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Indian market: 10-digit mobile with optional +91, GSTIN/FSSAI on the printed bill,
            // UPI VPA for QR collection, tax + currency defaults, owner linkage.
            $table->string('phone', 20)->nullable();
            $table->string('alternate_phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 80)->nullable()->index();
            $table->string('state', 80)->nullable()->index();
            $table->string('pincode', 10)->nullable()->index();
            $table->string('gstin', 15)->nullable()->index();
            $table->string('fssai_license', 30)->nullable();
            $table->string('upi_vpa', 100)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->decimal('default_gst_rate', 5, 2)->default(5.00);
            $table->boolean('is_gst_enabled')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('print_header')->nullable();
            $table->string('print_footer')->nullable();
            $table->string('logo_path')->nullable();
            $table->foreignId('owner_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_stores');
    }
};