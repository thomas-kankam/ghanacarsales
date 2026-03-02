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
            $table->string('token', 5);
            $table->string('actor_id');
            $table->string('guard');
            $table->string('type');
            $table->string('channel');
            $table->timestamp('expires_at');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['actor_id', 'token']);
            $table->index('expires_at');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
