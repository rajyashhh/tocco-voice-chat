<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            if (!Schema::hasColumn('gift_logs', 'total')) {
                Schema::table('gift_logs', function (Blueprint $table) {
                    $table->unsignedDecimal('total', 12, 2)->nullable();
                });
            }
        } catch (\Throwable $e) {
            // Column already exists or another DB error — safe to ignore
            // SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'total'
            if (str_contains($e->getMessage(), '1060') || str_contains($e->getMessage(), 'Duplicate column')) {
                // Already exists, nothing to do
                return;
            }
            throw $e;
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gift_logs', 'total')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->dropColumn('total');
            });
        }
    }
};
