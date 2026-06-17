<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slush_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_uuid')->unique();

            // Pipeline state: pending -> processing -> done | failed
            $table->string('status')->default('pending')->index();

            $table->string('uploaded_image_path')->nullable();
            $table->string('generated_avatar_path')->nullable();
            $table->boolean('used_fallback')->default(false);

            // Raw fun analysis returned by the AI service.
            $table->json('ai_response_json')->nullable();

            // Denormalised columns for quick admin reporting.
            $table->string('character_name')->nullable();
            $table->string('slush_flavor')->nullable()->index();

            $table->string('coupon_code')->nullable()->index();
            $table->string('coupon_status')->default('unused');

            // When the original uploaded photo was purged (privacy cleanup).
            $table->timestamp('image_deleted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slush_sessions');
    }
};
