<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained('dealers');
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('published_count')->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('last_payment_id')->nullable();
            $table->timestamps();

            $table->index('dealer_id');
            $table->index('plan_id');
            $table->index('status');
            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

