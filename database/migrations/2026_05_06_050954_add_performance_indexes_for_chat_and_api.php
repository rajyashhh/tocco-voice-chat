<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These indexes significantly improve performance for:
     * 1. UpdateLastSeen middleware (chat_messages, chat_rooms)
     * 2. Gift-related queries (gift_logs)
     *
     * Expected Impact: Reduce P99 latency from 5.79s to <500ms
     */
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // For markMessagesAsReceivedInBatch query:
            // WHERE chat_room_id = ? AND user_id != ? AND status = 'sended'
            if (!$this->indexExists('chat_messages', 'idx_chat_update')) {
                $table->index(['chat_room_id', 'user_id', 'status'], 'idx_chat_update');
            }
        });

        Schema::table('chat_rooms', function (Blueprint $table) {
            // For getUserChatRooms query:
            // WHERE user_id = ? OR user_id2 = ?
            if (!$this->indexExists('chat_rooms', 'idx_user_rooms')) {
                $table->index(['user_id', 'user_id2'], 'idx_user_rooms');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // For frequent last_seen_at updates
            if (!$this->indexExists('users', 'idx_last_seen')) {
                $table->index(['last_seen_at', 'online'], 'idx_last_seen');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if ($this->indexExists('chat_messages', 'idx_chat_update')) {
                $table->dropIndex('idx_chat_update');
            }
        });

        Schema::table('chat_rooms', function (Blueprint $table) {
            if ($this->indexExists('chat_rooms', 'idx_user_rooms')) {
                $table->dropIndex('idx_user_rooms');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'idx_last_seen')) {
                $table->dropIndex('idx_last_seen');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $conn = Schema::getConnection();
        $dbSchemaManager = $conn->getDoctrineSchemaManager();
        $doctrineTable = $dbSchemaManager->introspectTable($table);

        return $doctrineTable->hasIndex($index);
    }
};
