<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The lucky-gifts reports page filters fair_luck_transactions (~600K rows/day)
 * by user inside a date window. A single-column user_id index forces a scan of
 * the user's entire history; (user_id, created_at) makes it a tight range read.
 * created_at alone is already covered by idx_flt_created_at (2026_04_28).
 */
return new class extends Migration
{
    private const INDEX = 'idx_flt_user_created';

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    public function up(): void
    {
        if (!Schema::hasTable('fair_luck_transactions')) {
            return;
        }

        Schema::table('fair_luck_transactions', function (Blueprint $table) {
            if (!$this->hasIndex('fair_luck_transactions', self::INDEX)) {
                $table->index(['user_id', 'created_at'], self::INDEX);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fair_luck_transactions')) {
            return;
        }

        Schema::table('fair_luck_transactions', function (Blueprint $table) {
            if ($this->hasIndex('fair_luck_transactions', self::INDEX)) {
                $table->dropIndex(self::INDEX);
            }
        });
    }
};
