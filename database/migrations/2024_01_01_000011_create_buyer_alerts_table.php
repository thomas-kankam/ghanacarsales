<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->onDelete('cascade');
            $table->string('mobile_number', 20);
            $table->string('email')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->foreignId('model_id')->nullable()->constrained('car_models')->onDelete('set null');
            $table->year('min_year')->nullable();
            $table->year('max_year')->nullable();
            $table->integer('min_mileage')->nullable();
            $table->integer('max_mileage')->nullable();
            $table->enum('mileage_unit', ['kilometers', 'miles'])->nullable();
            $table->decimal('min_price', 15, 2)->nullable();
            $table->decimal('max_price', 15, 2)->nullable();
            $table->boolean('swap_deals')->nullable();
            $table->boolean('aircon')->nullable();
            $table->boolean('registered')->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric'])->nullable();
            $table->enum('transmission', ['manual', 'automatic'])->nullable();
            $table->string('colour')->nullable();
            $table->enum('location', [
                'Greater Accra', 'Ashanti', 'Western', 'Eastern', 'Central', 
                'Northern', 'Upper East', 'Upper West', 'Volta', 'Brong Ahafo',
                'Western North', 'Ahafo', 'Bono', 'Bono East', 'Oti', 'North East'
            ])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('buyer_id');
            $table->index('mobile_number');
            $table->index('is_active');
            $table->index(['brand_id', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_alerts');
    }
};
