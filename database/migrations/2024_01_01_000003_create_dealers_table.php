<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->uuid('dealer_slug')->unique();
            $table->string('phone_number', 12)->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('business_type')->nullable();
            $table->string('full_name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('dealer_code')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('landmark')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_onboarded')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone_number');
            $table->index('dealer_slug');
            $table->index('email');
            $table->index('business_type');
            $table->index('full_name');
            $table->index('business_name');
            $table->index('is_active');
            $table->index('is_onboarded');
            $table->index('dealer_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
