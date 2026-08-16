<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRoomsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("DROP VIEW IF EXISTS rooms_view_with_today_rank");
        DB::statement('
        CREATE VIEW rooms_view_with_today_rank AS
        SELECT *,(SELECT SUM(giftPrice) FROM gift_logs WHERE roomowner_id = r.uid AND CAST(created_at AS DATE) = CAST(NOW() AS DATE)) AS today_rank FROM rooms as r;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS rooms_view_with_today_rank");
    }
}
