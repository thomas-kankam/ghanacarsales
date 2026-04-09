<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cars')) {
            return;
        }

        Schema::table('cars', function (Blueprint $table) {
            if (! Schema::hasColumn('cars', 'region')) {
                $table->string('region')->nullable()->after('brand');
            }
            if (! Schema::hasColumn('cars', 'location')) {
                $table->string('location')->nullable()->after('region');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cars')) {
            return;
        }

        Schema::table('cars', function (Blueprint $table) {
            if (Schema::hasColumn('cars', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('cars', 'region')) {
                $table->dropColumn('region');
            }
        });
    }
};
