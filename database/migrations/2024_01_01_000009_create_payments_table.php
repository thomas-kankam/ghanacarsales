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
            $table->string('status')->nullable();
            $table->string("phone_number")->nullable();
            $table->string('network')->nullable();
            $table->string('reference_id')->nullable()->unique();
            $table->decimal('plan_price', 15, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->json('car_slugs')->nullable();
            $table->json('plan_details')->nullable();
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
