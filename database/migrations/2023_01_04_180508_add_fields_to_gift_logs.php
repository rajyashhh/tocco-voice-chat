<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToGiftLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->dropColumn (['family_id']);
        });
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->unsignedInteger ('sender_family_id')->nullable ();
            $table->unsignedInteger ('receiver_family_id')->nullable ();
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
            //
        });
    }
}
