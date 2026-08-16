<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtherFieldsToGiftLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->unsignedInteger ('agency_id')->nullable ();
            $table->decimal ('agency_obtain',14,2,true)->nullable ()->default (0);
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
