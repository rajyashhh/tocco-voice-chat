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
     * Enforces the natural key (room_id, user_id) on room_visitors so that the
     * join/leave path can use an atomic INSERT ... ON DUPLICATE / insertOrIgnore
     * keyed on a single deterministic index, eliminating the SELECT-then-INSERT
     * gap/insert-intention locks that produced the InnoDB deadlock cycle.
     */
    public function up(): void
    {
        // (1) MANDATORY dedup before adding UNIQUE, otherwise the UNIQUE add fails 1062.
        // Keep the smallest id per (room_id, user_id) pair.
        DB::statement('
            DELETE r1 FROM room_visitors r1
            INNER JOIN room_visitors r2
                ON r1.room_id = r2.room_id
                AND r1.user_id = r2.user_id
                AND r1.id > r2.id
        ');

        // (2) Drop redundant single-column / composite indexes that become a
        // prefix of the new UNIQUE(room_id, user_id), guarded by isset() on the
        // schema manager (same pattern as 2026_05_20_052435).
        Schema::table('room_visitors', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('room_visitors');

            // idx_room_visitors_room_id (room_id) is fully covered as a prefix of
            // UNIQUE(room_id, user_id).
            if (isset($indexes['idx_room_visitors_room_id'])) {
                $table->dropIndex('idx_room_visitors_room_id');
            }

            // idx_room_visitors_room_user (room_id, user_id) is superseded by the
            // UNIQUE constraint on the same columns.
            if (isset($indexes['idx_room_visitors_room_user'])) {
                $table->dropIndex('idx_room_visitors_room_user');
            }
        });

        // (3) Add the UNIQUE(room_id, user_id) constraint that turns the upsert
        // into a single-key deterministic operation.
        Schema::table('room_visitors', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('room_visitors');

            if (!isset($indexes['uq_room_visitors_room_user'])) {
                $table->unique(['room_id', 'user_id'], 'uq_room_visitors_room_user');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_visitors', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('room_visitors');

            if (isset($indexes['uq_room_visitors_room_user'])) {
                $table->dropUnique('uq_room_visitors_room_user');
            }

            // Restore the indexes dropped in up() so the schema is reversible.
            if (!isset($indexes['idx_room_visitors_room_user'])) {
                $table->index(['room_id', 'user_id'], 'idx_room_visitors_room_user');
            }

            if (!isset($indexes['idx_room_visitors_room_id'])) {
                $table->index('room_id', 'idx_room_visitors_room_id');
            }
        });
    }
};
