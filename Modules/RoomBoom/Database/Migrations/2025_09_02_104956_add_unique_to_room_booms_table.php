<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniqueToRoomBoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('room_booms', function (Blueprint $table) {
            $table->unique(['total_room_gift_id', 'room_boom_level_id'], 'unique_roomboom_level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('room_booms', function (Blueprint $table) {
            $table->dropUnique('unique_roomboom_level');
        });
    }
}
