<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->index('room_status', 'idx_room_status');
            $table->index(['pin', 'hour_hot', 'session'], 'idx_pin_hot_session');
            $table->index('type', 'idx_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('country_id', 'idx_country');
            $table->index(['lat', 'long'], 'idx_user_location');
        });

        Schema::table('packs', function (Blueprint $table) {
            $table->index(['type', 'is_used', 'expire'], 'idx_type_used_expire');
        });

        Schema::table('entered_rooms', function (Blueprint $table) {
            $table->index(['uid', 'entered_at'], 'idx_uid_entered_at');
        });

        Schema::table('box_uses', function (Blueprint $table) {
            $table->index(['not_used_num', 'end_at'], 'idx_lucky_flag');
        });

        Schema::table('pick_box_lists', function (Blueprint $table) {
            $table->index(['user_id', 'box_user_id'], 'idx_user_pick');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('idx_room_status');
            $table->dropIndex('idx_pin_hot_session');
            $table->dropIndex('idx_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_country');
            $table->dropIndex('idx_user_location');
        });

        Schema::table('packs', function (Blueprint $table) {
            $table->dropIndex('idx_type_used_expire');
        });

        Schema::table('entered_rooms', function (Blueprint $table) {
            $table->dropIndex('idx_uid_entered_at');
        });

        Schema::table('box_uses', function (Blueprint $table) {
            $table->dropIndex('idx_lucky_flag');
        });

        Schema::table('pick_box_lists', function (Blueprint $table) {
            $table->dropIndex('idx_user_pick');
        });
    }
};
