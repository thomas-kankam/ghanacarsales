<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('payment_slug')->unique();
            $table->string('dealer_slug');
            $table->string('plan_name')->nullable();
            $table->string('plan_slug')->nullable();
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->string('status');
            $table->string("phone_number")->nullable();
            $table->string('network')->nullable();
            $table->string('reference_id')->nullable()->unique();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->nullable();
            $table->json('car_slugs')->nullable();
            $table->timestamps();

            $table->index('payment_slug');
            $table->index('dealer_slug');
            $table->index('status');
            $table->index('reference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
