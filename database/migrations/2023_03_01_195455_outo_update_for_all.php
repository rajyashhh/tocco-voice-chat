<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OutoUpdateForAll extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("DROP EVENT IF EXISTS `update_users_every_month`; CREATE EVENT `update_users_every_month` ON SCHEDULE EVERY 1 MONTH STARTS '2023-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE DO UPDATE users SET old_usd = old_usd + target_usd - target_token_usd , target_usd = 0 , target_token_usd = 0");
        DB::unprepared ("DROP EVENT IF EXISTS `update_agencies_every_month`; CREATE EVENT `update_agencies_every_month` ON SCHEDULE EVERY 1 MONTH STARTS '2023-01-01 00:00:00' ON COMPLETION PRESERVE ENABLE DO UPDATE agencies SET old_usd = old_usd + target_usd - target_token_usd , target_usd = 0 , target_token_usd = 0");
        DB::unprepared(
            'DROP TRIGGER IF EXISTS `update_agrncy_target`; CREATE TRIGGER `update_agrncy_target` AFTER UPDATE ON `user_target` FOR EACH ROW UPDATE agencies SET target_usd = ( SELECT SUM(agency_obtain) FROM user_target WHERE agency_id = NEW.agency_id AND add_month = MONTH(CURDATE()) AND add_year = YEAR(CURDATE()) ) WHERE id = NEW.agency_id;'
        );
        DB::unprepared(
            'DROP TRIGGER IF EXISTS `update_agrncy_target1`; CREATE TRIGGER `update_agrncy_target1` AFTER INSERT ON `user_target` FOR EACH ROW UPDATE agencies SET target_usd = ( SELECT SUM(agency_obtain) FROM user_target WHERE agency_id = NEW.agency_id AND add_month = MONTH(CURDATE()) AND add_year = YEAR(CURDATE()) ) WHERE id = NEW.agency_id;'
        );
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
