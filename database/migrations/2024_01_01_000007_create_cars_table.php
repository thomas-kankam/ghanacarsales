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
            $table->string('model');
            $table->string('brand');
            $table->year('year_of_manufacture');
            $table->integer('mileage');
            $table->string('mileage_unit')->nullable();
            $table->boolean('swap_deals')->default(false);
            $table->decimal('price', 10, 2);
            $table->decimal('plan_price', 10, 2);
            $table->boolean('aircon')->default(false);
            $table->boolean('registered')->default(false);
            $table->year('registration_year')->nullable();
            $table->string('fuel_type');
            $table->string('transmission');
            $table->string('colour');
            $table->json("images")->nullable();
            $table->json("features")->nullable();
            $table->string('status')->nullable();
            $table->longText('description')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('plan_name')->nullable();
            $table->string('plan_slug')->nullable();
            $table->string('duration_days')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('car_slug');
            $table->index('dealer_slug');
            $table->index('brand');
            $table->index('model');
            $table->index('status');
            $table->index('expiry_date');
            $table->index('price');
            $table->index('year_of_manufacture');
            $table->index(['status', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
