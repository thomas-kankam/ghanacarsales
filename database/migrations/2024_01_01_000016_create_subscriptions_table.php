<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('dealer_slug');
            $table->string('subscription_slug');
            $table->string('plan_slug');
            $table->string('plan_name');
            $table->string('duration_days');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->string('status');
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();

            $table->index('dealer_slug');
            $table->index('plan_slug');
            $table->index('status');
            $table->index('starts_at');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
