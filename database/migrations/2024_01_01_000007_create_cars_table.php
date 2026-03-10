<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('car_slug')->unique();
            $table->string('dealer_slug')->nullable();
            $table->string('model')->nullable();
            $table->string('brand')->nullable();
            $table->year('year_of_manufacture')->nullable();
            $table->integer('mileage')->nullable();
            $table->string('mileage_unit')->nullable();
            $table->boolean('swap_deals')->default(false);
            $table->decimal('price', 12, 2)->nullable();
            $table->boolean('aircon')->default(false);
            $table->boolean('registered')->default(false);
            $table->year('registration_year')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->string('colour')->nullable();
            $table->longText('description')->nullable();
            $table->json("images")->nullable();
            $table->string('status')->nullable();
            $table->string('plan_slug')->nullable();
            $table->decimal('plan_price', 12, 2)->nullable();
            $table->json('plan_details')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('dealer_slug');
            $table->index('status');
            $table->index(['start_date', 'expiry_date']);
            $table->index(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
