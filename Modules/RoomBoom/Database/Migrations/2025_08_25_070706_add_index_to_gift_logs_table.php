<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToGiftLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index(['room_id', 'room_boom_level', 'start_boom_ranking', 'created_at'], 'gift_logs_room_boom_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->dropIndex('gift_logs_room_boom_idx');
        });
    }
}
