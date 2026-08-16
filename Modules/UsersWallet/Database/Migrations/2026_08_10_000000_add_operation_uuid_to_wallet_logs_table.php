<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_logs', 'operation_uuid')) {
                $table->string('operation_uuid', 64)->nullable()->after('related_id');
            }
        });

        // Backstop against double-credit on job retry: a given (operation, role) is
        // applied at most once. NULL operation_uuid rows (legacy / non-salary writes)
        // are exempt because MySQL treats NULLs as distinct in a unique index.
        $indexExists = collect(Schema::getConnection()->select(
            "SHOW INDEX FROM wallet_logs WHERE Key_name = 'wallet_logs_operation_uuid_type_unique'"
        ))->isNotEmpty();

        if (!$indexExists) {
            Schema::table('wallet_logs', function (Blueprint $table) {
                $table->unique(['operation_uuid', 'type'], 'wallet_logs_operation_uuid_type_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('wallet_logs', function (Blueprint $table) {
            $table->dropUnique('wallet_logs_operation_uuid_type_unique');
            $table->dropColumn('operation_uuid');
        });
    }
};
