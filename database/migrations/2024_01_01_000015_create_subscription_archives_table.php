<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_archives', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_slug')->nullable();
            $table->string('dealer_slug')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('duration_days')->nullable();
            $table->decimal('price', 15, 2);
            $table->string('plan_slug')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->index('subscription_slug');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_archives');
    }
};
