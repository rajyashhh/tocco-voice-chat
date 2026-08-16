<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supporting indexes for the Games User Reports pages (CoinGameUserService).
 *
 * The detail grids scan coin_game_users_archive by (user_id) + created_at range,
 * and the main grid scans coin_game_users_daily_aggregated by (game_id) + date.
 * Existing indexes on archive: (user_id, game_id), (created_at), and a covering
 * (game_id, user_id, type, coins, created_at). What is missing is a (user_id,
 * created_at) path for the per-user detail/round drill-downs and a (game_id, date)
 * path for the daily-aggregated game filter.
 *
 * Idempotent: each index is created only if absent (raw SHOW INDEX check) so the
 * migration is safe to re-run and across environments that may already have them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing(
            'coin_game_users_archive',
            'cgu_archive_user_created_index',
            '(`user_id`, `created_at`)'
        );

        $this->addIndexIfMissing(
            'coin_game_users_daily_aggregated',
            'cgu_daily_game_date_index',
            '(`game_id`, `date`)'
        );
    }

    public function down(): void
    {
        $this->dropIndexIfExists('coin_game_users_archive', 'cgu_archive_user_created_index');
        $this->dropIndexIfExists('coin_game_users_daily_aggregated', 'cgu_daily_game_date_index');
    }

    private function indexExists(string $table, string $index): bool
    {
        if (!Schema::hasTable($table)) {
            return true; // treat missing table as "nothing to do"
        }

        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);

        return !empty($rows);
    }

    private function addIndexIfMissing(string $table, string $index, string $columns): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $index)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$index}` {$columns}");
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (Schema::hasTable($table) && $this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
};
