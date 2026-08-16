<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
 
        Schema::table('fair_luck_wallets', function (Blueprint $table) {
            if (!$this->uniqueConstraintExists('fair_luck_wallets', 'wallet_type')) {
                $table->unique('wallet_type')->comment('Ensures one row per wallet type');
            }
        });

 
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!$this->indexExists('chat_messages', 'idx_room_user_status')) {
                $table->index(['chat_room_id', 'user_id', 'status'], 'idx_room_user_status')
                    ->comment('Optimizes bulk status updates: WHERE chat_room_id IN (...) AND user_id != X AND status = Y');
            }

            if (!$this->indexExists('chat_messages', 'idx_status')) {
                $table->index('status', 'idx_status')
                    ->comment('Optimizes status filtering');
            }

            if (!$this->indexExists('chat_messages', 'idx_room_created')) {
                $table->index(['chat_room_id', 'created_at'], 'idx_room_created')
                    ->comment('Optimizes room message retrieval with time filtering');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fair_luck_wallets', function (Blueprint $table) {
            if ($this->uniqueConstraintExists('fair_luck_wallets', 'wallet_type')) {
                $table->dropUnique('fair_luck_wallets_wallet_type_unique');
            }
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            if ($this->indexExists('chat_messages', 'idx_room_user_status')) {
                $table->dropIndex('idx_room_user_status');
            }
            if ($this->indexExists('chat_messages', 'idx_status')) {
                $table->dropIndex('idx_status');
            }
            if ($this->indexExists('chat_messages', 'idx_room_created')) {
                $table->dropIndex('idx_room_created');
            }
        });
    }

    /**
     * Helper method to check if a unique constraint exists
     */
    private function uniqueConstraintExists(string $table, string $column): bool
    {
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND CONSTRAINT_NAME LIKE '%unique%'",
            [$table, $column]
        );
        return !empty($constraints);
    }

    /**
     * Helper method to check if an index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $index]
        );
        return !empty($indexes);
    }
};
