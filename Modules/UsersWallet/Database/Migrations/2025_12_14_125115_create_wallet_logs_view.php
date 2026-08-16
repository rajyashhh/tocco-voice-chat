<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW all_user_logs AS

            /* ===== Diamond Logs ===== */
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
                NULL AS feature,
                NULL AS wallet_id,
                NULL AS before_amount,
                NULL AS after_amount,
                NULL AS operation,
                NULL AS related_id
            FROM user_diamond_logs

            UNION ALL

            /* ===== Coin Logs ===== */
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
                feature_type AS feature,
                NULL AS wallet_id,
                NULL AS before_amount,
                NULL AS after_amount,
                NULL AS operation,
                NULL AS related_id
            FROM user_coin_logs

            UNION ALL

            /* ===== Wallet Logs ===== */
            SELECT
                CONCAT('W', id) AS id,
                user_id,
                amount,
                NULL AS coin,
                before_amount AS amount_before,
                type,
                operation AS sub_type,
                NULL AS item_name,
                NULL AS get_by_id,
                created_at,
                updated_at,
                'wallet' AS feature_type,
                NULL AS feature,
                wallet_id,
                before_amount,
                after_amount,
                operation,
                related_id
            FROM wallet_logs
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS all_user_logs");
    }
};
