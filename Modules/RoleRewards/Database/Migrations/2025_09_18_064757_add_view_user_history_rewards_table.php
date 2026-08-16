<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasTable('admin_roles') && Schema::hasTable('milestones') && Schema::hasTable('user_history_rewards')) {
            DB::statement("
                CREATE OR REPLACE VIEW v_user_history_rewards AS
                SELECT 
                    uhr.id,
                    uhr.user_id,
                    uhr.receive_type,
                    
                    -- النوع (Role أو Milestone)
                    SUBSTRING_INDEX(uhr.receive_type, ':', 1) AS receive_category,
                    
                    -- الـ id بعد النقطتين
                    CAST(SUBSTRING_INDEX(uhr.receive_type, ':', -1) AS UNSIGNED) AS receive_ref_id,
                    
                    -- الاسم حسب النوع
                    CASE 
                        WHEN SUBSTRING_INDEX(uhr.receive_type, ':', 1) = 'Role'
                            THEN r.name
                        WHEN SUBSTRING_INDEX(uhr.receive_type, ':', 1) = 'Milestone'
                            THEN m.name
                        ELSE NULL
                    END AS receive_name,
                    
                    uhr.rewardable_id,
                    uhr.rewardable_type,
                    uhr.created_at
                FROM user_history_rewards uhr
                LEFT JOIN admin_roles r 
                    ON (SUBSTRING_INDEX(uhr.receive_type, ':', 1) = 'Role' 
                        AND r.id = CAST(SUBSTRING_INDEX(uhr.receive_type, ':', -1) AS UNSIGNED))
                LEFT JOIN milestones m 
                    ON (SUBSTRING_INDEX(uhr.receive_type, ':', 1) = 'Milestone' 
                        AND m.id = CAST(SUBSTRING_INDEX(uhr.receive_type, ':', -1) AS UNSIGNED))
            ");
        }
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_user_history_rewards");
    }
};
