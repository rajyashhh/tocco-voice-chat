<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * search-user-agency (§Lane1): users.special_id has no index, so the user search
 * (UserRepository::filterUserNew) cannot use it for the prefix lookup
 * `special_id LIKE 'term%'` and falls back to a full table scan.
 *
 * A plain B-tree index makes that prefix predicate sargable (range scan). The
 * sibling uuid column already carries a UNIQUE index, so uuid prefix search is
 * covered without further DDL — only special_id needs this index.
 *
 * Added online (INPLACE/LOCK=NONE on MySQL 8) — no column change, no table
 * rebuild. The information_schema guard mirrors 2026_05_17_000001 /
 * 2026_06_01_180000 so re-running (or a hand-applied index) is a no-op.
 */
return new class extends Migration
{
    private const INDEX = 'idx_users_special_id';

    public function up(): void
    {
        if ($this->indexExists()) {
            return;
        }

        Schema::table('users', function ($table) {
            $table->index('special_id', self::INDEX);
        });
    }

    public function down(): void
    {
        if (!$this->indexExists()) {
            return;
        }

        Schema::table('users', function ($table) {
            $table->dropIndex(self::INDEX);
        });
    }

    private function indexExists(): bool
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics '
                . 'WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, 'users', self::INDEX]
        );

        return !empty($rows);
    }
};
