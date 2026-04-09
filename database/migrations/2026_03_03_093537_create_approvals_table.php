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
            $table->string('approval_slug', 36)->unique()->nullable();
            $table->string('car_slug')->nullable();
            $table->string('dealer_slug')->nullable();
            $table->string('dealer_code')->nullable()->unique();
            $table->boolean('admin_approval')->default(false);
            $table->string('admin_slug')->nullable();
            $table->timestamp('admin_approval_at')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->string('dealer_name')->nullable();
            $table->string('payment_slug')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('car_slug');
            $table->index('dealer_slug');
            $table->index('approval_slug');
            $table->index('status');
            $table->index('type');
            $table->index('payment_slug');
            $table->index('dealer_name');
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
