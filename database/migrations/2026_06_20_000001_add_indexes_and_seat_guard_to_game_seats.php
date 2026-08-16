<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4 — game_seats hot-path index + seat-overbooking guard.
     *
     * 1) Composite index (room_id, game_id, location) covering the sitDown
     *    seat-free check and the gameEnd seat-update lookup, which previously
     *    relied on three independent single-column indexes.
     *
     * 2) DB-enforced anti-overbooking guard. A seat is "actively occupied" only
     *    while location IS NOT NULL AND end_rank IS NULL (standUp nulls location,
     *    gameEnd sets end_rank). A naive UNIQUE(room_id,game_id,location) would
     *    clash with freed/historical rows. MySQL has no partial/filtered unique
     *    index, so we use a STORED generated column `active_location` that mirrors
     *    `location` ONLY while the seat is active and is NULL otherwise. MySQL
     *    permits unlimited rows whose unique-key tuple contains a NULL, so all
     *    freed/historical rows (active_location = NULL) coexist, while at most one
     *    active row can exist per (room_id, game_id, location). The race between
     *    two concurrent sitDown orders at the same seat is now rejected at the DB
     *    level (the loser hits the unique index), independent of app-level checks.
     */
    public function up(): void
    {
        Schema::table('game_seats', function (Blueprint $table) {
            $table->index(['room_id', 'game_id', 'location'], 'game_seats_room_game_location_idx');
        });

        // STORED generated column: equals location while seated, NULL once freed
        // (location nulled) or finished (end_rank set). Built via raw SQL because
        // the schema builder's storedAs across nullable source columns is brittle
        // on older MySQL; the expression below is portable to MySQL 5.7+/8.0.
        DB::statement("
            ALTER TABLE game_seats
            ADD COLUMN active_location INT
            GENERATED ALWAYS AS (
                CASE WHEN end_rank IS NULL AND location IS NOT NULL THEN location ELSE NULL END
            ) STORED
        ");

        // At most one ACTIVE seat per (room, game, location). Rows with
        // active_location = NULL (freed/historical) are exempt — NULLs are
        // considered distinct in a MySQL unique index.
        DB::statement("
            ALTER TABLE game_seats
            ADD UNIQUE INDEX game_seats_active_seat_unique (room_id, game_id, active_location)
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE game_seats DROP INDEX game_seats_active_seat_unique");
        DB::statement("ALTER TABLE game_seats DROP COLUMN active_location");

        Schema::table('game_seats', function (Blueprint $table) {
            $table->dropIndex('game_seats_room_game_location_idx');
        });
    }
};
