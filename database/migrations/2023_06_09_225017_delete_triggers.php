<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeleteTriggers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        DB::unprepared('DROP TRIGGER IF EXISTS update_agrncy_target');
//        DB::unprepared('DROP TRIGGER IF EXISTS update_agrncy_target1');
//        DB::unprepared('DROP TRIGGER IF EXISTS update_agrncy_target_after_change_agency');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
