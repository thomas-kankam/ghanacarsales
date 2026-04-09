<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('approvals') || !Schema::hasColumn('approvals', 'dealer_code')) {
            return;
        }

        $hasDuplicates = DB::table('approvals')
            ->select('dealer_code')
            ->whereNotNull('dealer_code')
            ->groupBy('dealer_code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException('Cannot add unique constraint: duplicate dealer_code values exist in approvals table.');
        }

        Schema::table('approvals', function (Blueprint $table) {
            $table->unique('dealer_code', 'approvals_dealer_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('approvals')) {
            return;
        }

        Schema::table('approvals', function (Blueprint $table) {
            $table->dropUnique('approvals_dealer_code_unique');
        });
    }
};

