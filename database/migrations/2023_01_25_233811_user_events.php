<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared ("DROP EVENT IF EXISTS `update_users_every_month`; CREATE EVENT `update_users_every_month` ON SCHEDULE EVERY 1 MONTH STARTS '2023-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE DO UPDATE users SET old_usd = old_usd + target_usd - target_token_usd , target_usd = 0 , target_token_usd = 0");
        DB::unprepared ("DROP EVENT IF EXISTS `update_agencies_every_month`; CREATE EVENT `update_agencies_every_month` ON SCHEDULE EVERY 1 MONTH STARTS '2023-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE DO UPDATE agencies SET old_usd = old_usd + target_usd - target_token_usd , target_usd = 0 , target_token_usd = 0");
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
