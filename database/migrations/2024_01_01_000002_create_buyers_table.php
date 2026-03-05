<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->string('buyer_slug')->unique();
            $table->string('phone_number')->unique();
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('verified')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone_number');
            $table->index('buyer_slug');
            $table->index('full_name');
            $table->index('email');
            $table->index('verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyers');
    }
};
