<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Performance fix for GET /api/rooms?filter=popular (home content, ~5s).
 *
 * The popular list filters on (room_status = 1, type = 'audio') and orders by
 * pin DESC, room_visitors_count DESC (computed via withCount, not indexable),
 * hour_hot DESC, top_room DESC.
 *
 * Existing indexes (2025_08_05) already cover (room_status, type, pin, hour_hot)
 * and (room_status, top_room, pin, hour_hot). What is missing is a composite
 * that lets the filter + the popular-specific top_room ordering be served
 * together: (room_status, type, top_room, hour_hot).
 *
 * NOTE: rooms has NO deleted_at column (the model does not use SoftDeletes), so
 * the filtering index is keyed on the real columns (room_status, type) only.
 *
 * Idempotent (checks existence + column presence) and reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            return;
        }

        // Filtering + popular ordering: WHERE room_status = 1 AND type = 'audio'
        // ORDER BY ... top_room DESC, hour_hot DESC
        if (
            $this->hasColumns('rooms', ['room_status', 'type', 'top_room', 'hour_hot'])
            && ! $this->indexExists('rooms', 'idx_rooms_status_type_top_hour')
        ) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->index(
                    ['room_status', 'type', 'top_room', 'hour_hot'],
                    'idx_rooms_status_type_top_hour'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rooms')) {
            return;
        }

        if ($this->indexExists('rooms', 'idx_rooms_status_type_top_hour')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropIndex('idx_rooms_status_type_top_hour');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return ! empty($indexes);
    }

    /**
     * Check that every given column exists on the table.
     */
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
