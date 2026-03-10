<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_items', function (Blueprint $table) {
            $table->id();
            $table->string('payment_slug', 36);
            $table->string('car_slug', 36);
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();

            $table->index('payment_slug');
            $table->index('car_slug');
            $table->index('price');
            $table->unique(['payment_slug', 'car_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_items');
    }
};
