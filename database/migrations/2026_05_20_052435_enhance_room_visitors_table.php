<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('room_visitors', function (Blueprint $table) {
            // Check and add indexes only if they don't exist
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('room_visitors');

            // Add composite index for common queries (room_id, user_id)
            if (!isset($indexes['idx_room_visitors_room_user'])) {
                $table->index(['room_id', 'user_id'], 'idx_room_visitors_room_user');
            }

            // Add index on created_at for time-based queries
            if (!isset($indexes['idx_room_visitors_created_at'])) {
                $table->index('created_at', 'idx_room_visitors_created_at');
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

            if (isset($indexes['idx_room_visitors_room_user'])) {
                $table->dropIndex('idx_room_visitors_room_user');
            }

            if (isset($indexes['idx_room_visitors_created_at'])) {
                $table->dropIndex('idx_room_visitors_created_at');
            }
        });
    }
};
