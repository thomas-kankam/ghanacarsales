<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_number', 20);
            $table->string('otp_code', 6);
            $table->enum('user_type', ['buyer', 'seller'])->default('seller');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['mobile_number', 'otp_code']);
            $table->index('expires_at');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
