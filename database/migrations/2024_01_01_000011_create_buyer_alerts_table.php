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
            $table->string('buyer_id')->nullable();
            $table->string('mobile_number', 20);
            $table->string('email')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->year('min_year')->nullable();
            $table->year('max_year')->nullable();
            $table->integer('min_mileage')->nullable();
            $table->integer('max_mileage')->nullable();
            $table->string('mileage_unit')->nullable();
            $table->decimal('min_price', 15, 2)->nullable();
            $table->decimal('max_price', 15, 2)->nullable();
            $table->boolean('swap_deals')->nullable();
            $table->boolean('aircon')->nullable();
            $table->boolean('registered')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->string('colour')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('buyer_id');
            $table->index('mobile_number');
            $table->index('is_active');
            $table->index(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_alerts');
    }
};
