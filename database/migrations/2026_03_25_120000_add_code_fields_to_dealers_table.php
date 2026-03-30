<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            if (!Schema::hasColumn('dealers', 'code_status')) {
                $table->string('code_status')->nullable()->after('dealer_code');
            }

            if (!Schema::hasColumn('dealers', 'code_assigned_at')) {
                $table->timestamp('code_assigned_at')->nullable()->after('code_status');
            }

            if (!Schema::hasColumn('dealers', 'code_revoked_at')) {
                $table->timestamp('code_revoked_at')->nullable()->after('code_assigned_at');
            }

            if (!Schema::hasColumn('dealers', 'reason')) {
                $table->text('reason')->nullable()->after('code_revoked_at');
            }
        });

        Schema::table('dealers', function (Blueprint $table) {
            try {
                $table->index('code_status');
            } catch (\Throwable $e) {
                // Index may already exist on fresh databases.
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            if (Schema::hasColumn('dealers', 'code_status')) {
                $table->dropColumn('code_status');
            }

            if (Schema::hasColumn('dealers', 'code_assigned_at')) {
                $table->dropColumn('code_assigned_at');
            }

            if (Schema::hasColumn('dealers', 'code_revoked_at')) {
                $table->dropColumn('code_revoked_at');
            }

            if (Schema::hasColumn('dealers', 'reason')) {
                $table->dropColumn('reason');
            }
        });
    }
};

