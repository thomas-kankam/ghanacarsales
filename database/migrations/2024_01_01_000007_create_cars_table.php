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
            $table->uuid('car_slug')->unique();
            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->foreignId('brand_id')->constrained('brands')->onDelete('restrict');
            $table->foreignId('model_id')->constrained('car_models')->onDelete('restrict');
            $table->year('year_of_manufacture');
            $table->integer('mileage');
            $table->enum('mileage_unit', ['kilometers', 'miles'])->default('kilometers');
            $table->decimal('price', 15, 2);
            $table->boolean('swap_deals')->default(false);
            $table->boolean('aircon')->default(false);
            $table->boolean('registered')->default(false);
            $table->year('registration_year')->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric'])->default('petrol');
            $table->enum('transmission', ['manual', 'automatic'])->default('manual');
            $table->string('colour');
            $table->enum('location', [
                'Greater Accra', 'Ashanti', 'Western', 'Eastern', 'Central', 
                'Northern', 'Upper East', 'Upper West', 'Volta', 'Brong Ahafo',
                'Western North', 'Ahafo', 'Bono', 'Bono East', 'Oti', 'North East'
            ]);
            $table->enum('status', ['pending', 'active', 'expired', 'sold', 'deleted'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('payment_made_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('car_slug');
            $table->index('seller_id');
            $table->index('brand_id');
            $table->index('model_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('price');
            $table->index('year_of_manufacture');
            $table->index('location');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
