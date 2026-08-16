<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        DB::statement("
            CREATE VIEW all_user_logs AS
            SELECT
                CONCAT('D', id) AS id,
                user_id,
                amount,
                coin,
                diamond_before AS amount_before,
                type,
                sub_type,
                item_name,
                get_by_id,
                created_at,
                updated_at,
                'diamond' AS feature_type,
                 null AS feature
            FROM user_diamond_logs

            UNION ALL

            SELECT
                CONCAT('C', id) AS id,
                user_id,
                amount,
                NULL AS coin,
                amount_before,
                type,
                sub_type,
                item_name,
                NULL AS get_by_id,
                created_at,
                updated_at,
                'coin' AS feature_type,
                feature_type AS feature
            FROM user_coin_logs;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS all_user_logs");
    }
};
