<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('dealer_slug');
            $table->string('car_slug');
            $table->boolean('is_notified')->default(false);
            $table->timestamps();

            $table->index('dealer_slug');
            $table->index('car_slug');
            $table->index('is_notified');
            $table->unique(['dealer_slug', 'car_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_notifications');
    }
};
