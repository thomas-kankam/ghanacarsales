<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_cars', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id');
            $table->string('car_id');
            $table->timestamps();

            $table->unique(['payment_id', 'car_id']);
            $table->index('payment_id');
            $table->index('car_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_cars');
    }
};
