<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalize the all_games schema (Phase 3 games cleanup):
 *   - hight       -> height        (typo fix; data preserved)
 *   - hight_image -> height_image  (typo fix; data preserved)
 *   - is_enable   -> real TINYINT(1) boolean (was VARCHAR storing '1'/'0'/'true')
 *
 * Data is preserved: the rename uses ALTER TABLE ... CHANGE which keeps every
 * value; is_enable is migrated by casting the legacy string values to 0/1 in
 * place before tightening the column type. No game rows are dropped.
 *
 * API CONTRACT: the games-list JSON output keys (high, high_safety, in_room,
 * is_hot, type, ...) are produced by AllGameResource / AllGameInRoomResource and
 * are NOT changed by this migration — only the underlying DB column names change.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- 1) hight -> height (keep VARCHAR type + existing values) ---
        if (Schema::hasColumn('all_games', 'hight') && !Schema::hasColumn('all_games', 'height')) {
            DB::statement('ALTER TABLE `all_games` CHANGE `hight` `height` VARCHAR(255) NULL');
        }

        // --- 2) hight_image -> height_image (keep VARCHAR type + existing values) ---
        if (Schema::hasColumn('all_games', 'hight_image') && !Schema::hasColumn('all_games', 'height_image')) {
            DB::statement('ALTER TABLE `all_games` CHANGE `hight_image` `height_image` VARCHAR(255) NULL');
        }

        // --- 3) is_enable VARCHAR -> TINYINT(1) (preserve truthy/falsey values) ---
        if (Schema::hasColumn('all_games', 'is_enable')) {
            // Normalize any legacy string representations to canonical '1'/'0'
            // BEFORE tightening the type so MySQL's implicit cast cannot turn a
            // value like 'true' into 0.
            DB::statement("
                UPDATE `all_games`
                SET `is_enable` = CASE
                    WHEN LOWER(TRIM(COALESCE(`is_enable`, ''))) IN ('1', 'true', 'yes', 'on') THEN '1'
                    ELSE '0'
                END
            ");

            DB::statement('ALTER TABLE `all_games` CHANGE `is_enable` `is_enable` TINYINT(1) NOT NULL DEFAULT 1');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('all_games', 'height') && !Schema::hasColumn('all_games', 'hight')) {
            DB::statement('ALTER TABLE `all_games` CHANGE `height` `hight` VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('all_games', 'height_image') && !Schema::hasColumn('all_games', 'hight_image')) {
            DB::statement('ALTER TABLE `all_games` CHANGE `height_image` `hight_image` VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('all_games', 'is_enable')) {
            DB::statement("ALTER TABLE `all_games` CHANGE `is_enable` `is_enable` VARCHAR(255) NOT NULL DEFAULT '1'");
        }
    }
};
