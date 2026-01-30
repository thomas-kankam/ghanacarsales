<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->uuid('seller_slug')->unique();
            $table->string('mobile_number', 20);
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->enum('seller_type', ['individual', 'dealer'])->default('individual');
            $table->string('business_name')->nullable();
            $table->string('business_location')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('mobile_number');
            $table->index('seller_slug');
            $table->index('email');
            $table->index('seller_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
