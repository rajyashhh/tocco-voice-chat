<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSomeColumnsInRooms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn (['play_num','free_mic']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedTinyInteger('play_num')->comment('مفتاح اللعبة الرقمي 1 على 0 إيقاف')->nullable()->default (0)->after ('is_recommended');
            $table->unsignedTinyInteger('free_mic')->nullable()->default (0)->comment('بت مجاني للميكروفون 1 عند 0 إيقاف')->after ('play_num');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rooms', function (Blueprint $table) {
            //
        });
    }
}
