<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMissingIndexesForAdminGrid extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasIndex('users', 'idx_device_token')) {
                $table->index('device_token', 'idx_device_token');
            }
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasIndex('chat_messages', 'idx_chatroom_status')) {
                $table->index(['chat_room_id', 'status'], 'idx_chatroom_status');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', 'idx_device_token')) {
                $table->dropIndex('idx_device_token');
            }
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasIndex('chat_messages', 'idx_chatroom_status')) {
                $table->dropIndex('idx_chatroom_status');
            }
        });
    }
}
