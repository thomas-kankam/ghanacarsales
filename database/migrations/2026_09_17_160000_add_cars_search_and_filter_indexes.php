<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cars')) {
            return;
        }

        Schema::table('cars', function (Blueprint $table) {
            $table->index(['status', 'expiry_date'], 'cars_status_expiry_index');
            $table->index('price', 'cars_price_index');
            $table->index('region', 'cars_region_index');
            $table->index('year_of_manufacture', 'cars_year_index');
            $table->index(['dealer_slug', 'status'], 'cars_dealer_status_index');
            $table->index('fuel_type', 'cars_fuel_type_index');
            $table->index('transmission', 'cars_transmission_index');
        });

        // FULLTEXT helps buyer free-text search on MySQL/MariaDB (shared hosting friendly).
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                'ALTER TABLE cars ADD FULLTEXT INDEX cars_search_fulltext (brand, model, description, location, region, colour)'
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cars')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            try {
                DB::statement('ALTER TABLE cars DROP INDEX cars_search_fulltext');
            } catch (\Throwable) {
                // Index may not exist on non-MySQL restores.
            }
        }

        Schema::table('cars', function (Blueprint $table) {
            $table->dropIndex('cars_status_expiry_index');
            $table->dropIndex('cars_price_index');
            $table->dropIndex('cars_region_index');
            $table->dropIndex('cars_year_index');
            $table->dropIndex('cars_dealer_status_index');
            $table->dropIndex('cars_fuel_type_index');
            $table->dropIndex('cars_transmission_index');
        });
    }
};
