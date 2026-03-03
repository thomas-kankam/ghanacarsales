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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('car_slug')->nullable();
            $table->string('dealer_slug')->nullable();
            $table->string('dealer_code')->nullable();
            $table->boolean('dealer_approval')->default(false);
            $table->boolean('admin_approval')->default(false);
            $table->timestamps();

            $table->index('car_slug');
            $table->index('dealer_slug');
            $table->index('dealer_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
