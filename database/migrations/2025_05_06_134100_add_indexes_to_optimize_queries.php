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
        Schema::table('packs', function (Blueprint $table) {
            $table->index('user_id', 'idx_packs_user_id');
            $table->index('expire', 'idx_packs_expire');
            $table->index('type', 'idx_packs_type');
            $table->index('is_used', 'idx_packs_is_used');
            $table->index('deleted_at', 'idx_packs_deleted_at');
        });

        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index('receiver_id', 'idx_gift_logs_receiver_id');
            $table->index('sender_id', 'idx_gift_logs_sender_id');
        });

        Schema::table('achievement_levels', function (Blueprint $table) {
            $table->index('id', 'idx_achievement_levels_id');
        });
    }

    public function down()
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropIndex('idx_packs_user_id');
            $table->dropIndex('idx_packs_expire');
            $table->dropIndex('idx_packs_type');
            $table->dropIndex('idx_packs_is_used');
            $table->dropIndex('idx_packs_deleted_at');
        });

        Schema::table('gift_logs', function (Blueprint $table) {
            $table->dropIndex('idx_gift_logs_receiver_id');
            $table->dropIndex('idx_gift_logs_sender_id');
        });

        Schema::table('achievement_levels', function (Blueprint $table) {
            $table->dropIndex('idx_achievement_levels_id');
        });
    }
};
