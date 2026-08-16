<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing uniqueness guards on relationship tables. These tables may
     * already hold duplicate rows on the live DB, so each duplicate set is
     * collapsed (keeping the lowest id) BEFORE the unique index is created,
     * otherwise the index would fail. NULL key columns are left untouched —
     * MySQL/MariaDB treat NULLs as distinct in unique indexes.
     */
    public function up(): void
    {
        // block_users (blocker_id, blocked_id)
        $this->deduplicate('block_users', ['blocker_id', 'blocked_id']);
        if (!$this->indexExists('block_users', 'uq_block')) {
            Schema::table('block_users', function (Blueprint $table) {
                $table->unique(['blocker_id', 'blocked_id'], 'uq_block');
            });
        }

        // reacts (chat_message_id, user_id)
        $this->deduplicate('reacts', ['chat_message_id', 'user_id']);
        if (!$this->indexExists('reacts', 'uq_react')) {
            Schema::table('reacts', function (Blueprint $table) {
                $table->unique(['chat_message_id', 'user_id'], 'uq_react');
            });
        }

        // pin_to_tops (user_id, chat_room_id)
        $this->deduplicate('pin_to_tops', ['user_id', 'chat_room_id']);
        if (!$this->indexExists('pin_to_tops', 'uq_pin')) {
            Schema::table('pin_to_tops', function (Blueprint $table) {
                $table->unique(['user_id', 'chat_room_id'], 'uq_pin');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Only the indexes are dropped; deduplicated rows are not restored
     * (deletion of duplicates is intentionally not reversible).
     *
     * Each unique index here is leftmost-prefixed by an FK column, so creating
     * it in up() made MariaDB silently drop that FK's auto-index (the unique
     * now covers the FK). Dropping the unique in down() would then fail with
     * errno 1553 ("needed in a foreign key constraint") because it is the only
     * remaining index covering that FK. The fix mirrors the up() displacement:
     * restore the single-column FK-covering index BEFORE dropping each unique,
     * returning every table to its exact pre-migration index state.
     */
    public function down(): void
    {
        $this->restoreFkCoveringIndex('pin_to_tops', 'user_id', 'pin_to_tops_user_id_foreign');
        if ($this->indexExists('pin_to_tops', 'uq_pin')) {
            Schema::table('pin_to_tops', function (Blueprint $table) {
                $table->dropUnique('uq_pin');
            });
        }

        $this->restoreFkCoveringIndex('reacts', 'chat_message_id', 'reacts_chat_message_id_foreign');
        if ($this->indexExists('reacts', 'uq_react')) {
            Schema::table('reacts', function (Blueprint $table) {
                $table->dropUnique('uq_react');
            });
        }

        $this->restoreFkCoveringIndex('block_users', 'blocker_id', 'block_users_blocker_id_foreign');
        if ($this->indexExists('block_users', 'uq_block')) {
            Schema::table('block_users', function (Blueprint $table) {
                $table->dropUnique('uq_block');
            });
        }
    }

    /**
     * Re-create the single-column index that covers an FK column, but only when
     * that FK still exists and no index currently covers it under the given
     * name. Restores the auto-index MariaDB dropped when the composite unique
     * was added, so the subsequent unique drop cannot orphan the FK.
     */
    private function restoreFkCoveringIndex(string $table, string $column, string $indexName): void
    {
        if (
            $this->foreignKeyExists($table, $indexName)
            && !$this->indexExists($table, $indexName)
        ) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName) {
                $blueprint->index($column, $indexName);
            });
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.table_constraints')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }

    /**
     * Delete duplicate rows on the given key columns, keeping the lowest id.
     * Rows where any key column is NULL are skipped (NULLs do not collide in a
     * MySQL/MariaDB unique index).
     */
    private function deduplicate(string $table, array $keys): void
    {
        [$first, $second] = $keys;

        $duplicates = DB::table($table)
            ->select($first, $second, DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->whereNotNull($first)
            ->whereNotNull($second)
            ->groupBy($first, $second)
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $row) {
            DB::table($table)
                ->where($first, $row->{$first})
                ->where($second, $row->{$second})
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
