<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_cars', function (Blueprint $table) {
            $table->id();
            $table->string('payment_slug');
            $table->string('car_slug');
            $table->timestamps();

            $table->index('payment_slug');
            $table->index('car_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_cars');
    }
};
