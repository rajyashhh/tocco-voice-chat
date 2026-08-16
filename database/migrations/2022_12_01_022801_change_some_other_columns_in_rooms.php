<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSomeOtherColumnsInRooms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn (['microphone','is_prohibit_sound']);
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('microphone')->default('0,0,0,0,0,0,0,0,0,0')->comment('معلومات الميكروفون فارغة 0 ، -1 يقفل الميكروفون ، والآخرون مستخدمون')->nullable()->after ('room_judge');
            $table->string('is_prohibit_sound')->default('0,0,0,0,0,0,0,0,0,0')->comment('سواء كان تعطيل الصوت امكانية تعطيل صوت الميكروفون 0 ليس ممنوع 1')->nullable()->after ('microphone');
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
