<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFamilyViewsTable extends Migration
{





    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        DB::statement("DROP VIEW IF EXISTS today_family_views");
//        DB::statement('
//        CREATE VIEW today_family_views AS
//        SELECT *,(SELECT SUM(giftPrice) FROM gift_logs WHERE sender_family_id = f.id AND CAST(created_at AS DATE) = CAST(NOW() AS DATE)) AS rank FROM families as f;
//        ');
//        DB::statement("DROP VIEW IF EXISTS week_family_views");
//        DB::statement('
//        CREATE VIEW week_family_views AS
//        SELECT *,(SELECT SUM(giftPrice) FROM gift_logs WHERE sender_family_id = f.id AND WEEK(CAST(created_at AS DATE)) = WEEK(CAST(NOW() AS DATE))) AS rank FROM families as f;
//        ');
//        DB::statement("DROP VIEW IF EXISTS month_family_views");
//        DB::statement('
//        CREATE VIEW month_family_views AS
//        SELECT *,(SELECT SUM(giftPrice) FROM gift_logs WHERE sender_family_id = f.id AND MONTH(CAST(created_at AS DATE)) = MONTH(CAST(NOW() AS DATE))) AS rank FROM families as f;
//        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        DB::statement("DROP VIEW IF EXISTS today_family_views");
//        DB::statement("DROP VIEW IF EXISTS week_family_views");
//        DB::statement("DROP VIEW IF EXISTS month_family_views");
    }
}
