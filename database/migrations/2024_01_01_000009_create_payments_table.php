<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('payment_slug')->unique();
            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->enum('payment_type', ['single', 'cart'])->default('single');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['momo'])->default('momo');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('transaction_id')->nullable()->unique();
            $table->integer('duration_days')->default(30);
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('payment_slug');
            $table->index('seller_id');
            $table->index('status');
            $table->index('transaction_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
