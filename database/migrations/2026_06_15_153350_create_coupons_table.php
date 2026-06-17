<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            // percent | fixed | free_topping
            $table->string('discount_type')->default('percent');
            // Numeric amount for percent/fixed; null for free_topping.
            $table->unsignedInteger('discount_value')->nullable();
            // Human readable label, e.g. "ลด 10%" or "ฟรีท็อปปิ้ง".
            $table->string('discount_label')->nullable();

            // unused | used
            $table->string('status')->default('unused')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            // Soft link to the originating session (no hard FK to keep
            // migration ordering simple for the MVP / SQLite).
            $table->unsignedBigInteger('slush_session_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
