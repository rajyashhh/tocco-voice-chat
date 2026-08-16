<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the ban lookups on the authenticated read path so the OR-tree
     * filters by uid/type before the per-row duration arithmetic, instead of a
     * full table scan.
     */
    public function up(): void
    {
        if (!$this->indexExists('bans', 'idx_bans_uid')) {
            DB::statement('ALTER TABLE bans ADD INDEX idx_bans_uid (uid), ALGORITHM=INPLACE, LOCK=NONE');
        }

        if (!$this->indexExists('bans', 'idx_bans_type')) {
            DB::statement('ALTER TABLE bans ADD INDEX idx_bans_type (type), ALGORITHM=INPLACE, LOCK=NONE');
        }

        if (!$this->indexExists('bans', 'idx_bans_uid_created_at')) {
            DB::statement('ALTER TABLE bans ADD INDEX idx_bans_uid_created_at (uid, created_at), ALGORITHM=INPLACE, LOCK=NONE');
        }
    }

    public function down(): void
    {
        Schema::table('bans', function ($table) {
            if ($this->indexExists('bans', 'idx_bans_uid')) {
                $table->dropIndex('idx_bans_uid');
            }
            if ($this->indexExists('bans', 'idx_bans_type')) {
                $table->dropIndex('idx_bans_type');
            }
            if ($this->indexExists('bans', 'idx_bans_uid_created_at')) {
                $table->dropIndex('idx_bans_uid_created_at');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select(
            'SELECT COUNT(*) as count
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?',
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
