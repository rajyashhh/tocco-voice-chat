<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAgencyTargetTrigger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS update_agrncy_target');
        DB::unprepared(
            'CREATE TRIGGER `update_agrncy_target` AFTER UPDATE ON `user_target` FOR EACH ROW UPDATE agencies SET target_usd = ( SELECT SUM(target_usd * target_agency_share / 100) FROM user_target WHERE agency_id = NEW.agency_id AND add_month = MONTH(CURDATE()) AND add_year = YEAR(CURDATE()) ) WHERE id = NEW.agency_id;'
        );
        DB::unprepared('DROP TRIGGER IF EXISTS update_agrncy_target1');
        DB::unprepared(
            'CREATE TRIGGER `update_agrncy_target1` AFTER INSERT ON `user_target` FOR EACH ROW UPDATE agencies SET target_usd = ( SELECT SUM(target_usd * target_agency_share / 100) FROM user_target WHERE agency_id = NEW.agency_id AND add_month = MONTH(CURDATE()) AND add_year = YEAR(CURDATE()) ) WHERE id = NEW.agency_id;'
        );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS update_agrncy_target');
        DB::unprepared('DROP TRIGGER IF EXISTS update_agrncy_target1');
    }
}
