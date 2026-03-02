<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->uuid('car_slug')->unique();
            $table->string('dealer_id');
            $table->string('brand');
            $table->string('model');
            $table->year('year_of_manufacture');
            $table->integer('mileage');
            $table->enum('mileage_unit', ['kilometers', 'miles'])->default('kilometers');
            $table->decimal('price', 15, 2);
            $table->boolean('swap_deals')->default(false);
            $table->boolean('aircon')->default(false);
            $table->boolean('registered')->default(false);
            $table->boolean('admin_approval')->default(false);
            $table->boolean('dealer_approval')->default(false);
            $table->string('dealer_code')->nullable();
            $table->boolean('is_published')->default(false);
            $table->year('registration_year')->nullable();
            $table->string('fuel_type');
            $table->string('transmission');
            $table->string('colour');
            $table->json("images")->nullable();
            // $table->enum('location', [
            //     'Greater Accra', 'Ashanti', 'Western', 'Eastern', 'Central',
            //     'Northern', 'Upper East', 'Upper West', 'Volta', 'Brong Ahafo',
            //     'Western North', 'Ahafo', 'Bono', 'Bono East', 'Oti', 'North East',
            // ]);
            // $table->enum('status', ['pending', 'active', 'expired', 'sold', 'deleted'])->default('pending');
            $table->string('status')->default('draft');
            $table->longText('description');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('payment_made_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('car_slug');
            $table->index('dealer_id');
            $table->index('brand');
            $table->index('model');
            $table->index('status');
            $table->index('expires_at');
            $table->index('price');
            $table->index('year_of_manufacture');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
