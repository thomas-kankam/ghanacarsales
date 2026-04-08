<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('brands') && Schema::hasColumn('brands', 'image')) {
            DB::statement('ALTER TABLE `brands` MODIFY `image` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('brands') && Schema::hasColumn('brands', 'image')) {
            DB::statement('ALTER TABLE `brands` MODIFY `image` JSON NULL');
        }
    }
};

