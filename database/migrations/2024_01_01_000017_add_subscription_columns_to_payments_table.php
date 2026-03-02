<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_id')->nullable()->after('dealer_id');
            $table->unsignedBigInteger('plan_id')->nullable()->after('subscription_id');
            $table->string('reference')->nullable()->after('transaction_id');
            $table->string('provider')->nullable()->after('payment_method');
            $table->string('channel')->nullable()->after('provider');
            $table->json('raw_callback')->nullable()->after('metadata');

            $table->index('subscription_id');
            $table->index('plan_id');

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            $table->foreign('plan_id')->references('id')->on('subscription_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['subscription_id', 'plan_id', 'reference', 'provider', 'channel', 'raw_callback']);
        });
    }
};

