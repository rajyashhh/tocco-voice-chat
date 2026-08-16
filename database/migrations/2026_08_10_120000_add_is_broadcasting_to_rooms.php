<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gate the lives/trending list on the moment the host ACTUALLY starts
 * broadcasting, not the moment the host opens the room over HTTP.
 *
 * Until now the lives list (RoomRepository::liveRooms) filtered on rooms.is_afk,
 * which EnteranceRoomServices::handleOwnerLogic sets to 1 the instant the host
 * ENTERS the room via HTTP (room preparation), well before any media is pushed.
 * Result: a still-preparing room shows in lives/trending and viewers walk into
 * an empty room.
 *
 * is_broadcasting is the authoritative "host is pushing media on the engine"
 * flag. It is set only when the room owner publishes a VIDEO track on the
 * UTD-Stream engine (track_published webhook) and cleared on stop-broadcast /
 * owner-leave / room-finished / end-live, with rooms:sync-occupancy as the
 * self-healing backstop.
 *
 * The composite index serves the hot lives query
 *   WHERE room_status = 1 AND type = 'live' AND is_broadcasting = 1
 * (mirrors idx_rooms_status_type_top_hour added for the popular list).
 *
 * Idempotent and reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            return;
        }

        if (! Schema::hasColumn('rooms', 'is_broadcasting')) {
            Schema::table('rooms', function (Blueprint $table) {
                // Placed after is_live so the two liveness flags sit together.
                $table->boolean('is_broadcasting')->default(false)->after('is_live');
            });
        }

        if (
            $this->hasColumns('rooms', ['room_status', 'type', 'is_broadcasting'])
            && ! $this->indexExists('rooms', 'idx_rooms_status_type_broadcasting')
        ) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->index(
                    ['room_status', 'type', 'is_broadcasting'],
                    'idx_rooms_status_type_broadcasting'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rooms')) {
            return;
        }

        if ($this->indexExists('rooms', 'idx_rooms_status_type_broadcasting')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropIndex('idx_rooms_status_type_broadcasting');
            });
        }

        if (Schema::hasColumn('rooms', 'is_broadcasting')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('is_broadcasting');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]));
    }

    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
};
