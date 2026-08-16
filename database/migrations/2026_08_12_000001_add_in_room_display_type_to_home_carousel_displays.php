<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: skip the ALTER if the enum already contains 'in_room'.
        $column = DB::selectOne("SHOW COLUMNS FROM `home_carousel_displays` LIKE 'display_type'");
        if ($column && !str_contains($column->Type, 'in_room')) {
            DB::statement("
                ALTER TABLE `home_carousel_displays`
                MODIFY `display_type`
                ENUM('discover', 'home_top', 'home_middle', 'live', 'room', 'country', 'in_room')
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
            ");
        }

        // Backward compatibility: the in-room banner widget used to render the
        // 'home_top' placement, so every banner currently visible inside rooms
        // rides on that flag. Mirror each home_top row into an in_room row
        // (same status/end_at/duration) so live behavior is unchanged at deploy
        // time — admins can then toggle in_room independently. Raw insert on
        // purpose: the model's creating() hook would recompute end_at.
        DB::statement("
            INSERT INTO home_carousel_displays
                (home_carousel_id, display_type, end_at, duration, duration_unit, status, created_at, updated_at)
            SELECT d.home_carousel_id, 'in_room', d.end_at, d.duration, d.duration_unit, d.status, d.created_at, NOW()
            FROM home_carousel_displays d
            WHERE d.display_type = 'home_top'
              AND NOT EXISTS (
                  SELECT 1 FROM home_carousel_displays x
                  WHERE x.home_carousel_id = d.home_carousel_id
                    AND x.display_type = 'in_room'
              )
        ");
    }

    public function down(): void
    {
        DB::table('home_carousel_displays')->where('display_type', 'in_room')->delete();

        DB::statement("
            ALTER TABLE `home_carousel_displays`
            MODIFY `display_type`
            ENUM('discover', 'home_top', 'home_middle', 'live', 'room', 'country')
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
        ");
    }
};
