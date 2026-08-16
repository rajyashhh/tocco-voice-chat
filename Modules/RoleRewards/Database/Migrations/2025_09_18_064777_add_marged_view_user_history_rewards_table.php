<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('admin_roles') &&
            Schema::hasTable('milestones') &&
            Schema::hasTable('user_history_rewards')
        ) {
            DB::statement("
            CREATE OR REPLACE VIEW v_user_history_rewards AS
            SELECT 
                uhr.id,
                uhr.user_id,
                uhr.receive_type,
                SUBSTRING_INDEX(uhr.receive_type, ':', 1) AS receive_category,
                CAST(SUBSTRING_INDEX(uhr.receive_type, ':', -1) AS UNSIGNED) AS receive_ref_id,
                CASE 
                    WHEN SUBSTRING_INDEX(uhr.receive_type, ':', 1) = 'Role' THEN r.name
                    WHEN SUBSTRING_INDEX(uhr.receive_type, ':', 1) = 'Milestone' THEN m.name
                    ELSE NULL
                END AS receive_name,
                uhr.rewardable_id,
                uhr.rewardable_type,
                
                -- reward_value للأوسمة والكوينز
                CASE
                    WHEN uhr.rewardable_type IN ('Modules\\\\Achievement\\\\Entities\\\\Achievement','App\\\\Models\\\\User') 
                    THEN JSON_UNQUOTE(JSON_EXTRACT(uhr.extra, '$.reward'))
                    ELSE NULL
                END AS reward_value,
                
                -- اسم الصورة والاسم للمكافأة من الجداول مباشرة
                COALESCE(w.name, v.name, b.name, NULL) AS reward_name,
                COALESCE(w.img2, w.show_img, v.img, b.image, NULL) AS reward_img,
                uhr.extra AS reward_extra,
                uhr.created_at
            FROM user_history_rewards uhr
            LEFT JOIN admin_roles r 
                ON (SUBSTRING_INDEX(uhr.receive_type, ':', 1) = 'Role' 
                    AND r.id = CAST(SUBSTRING_INDEX(uhr.receive_type, ':', -1) AS UNSIGNED))
            LEFT JOIN milestones m 
                ON (SUBSTRING_INDEX(uhr.receive_type, ':', 1) = 'Milestone' 
                    AND m.id = CAST(SUBSTRING_INDEX(uhr.receive_type, ':', -1) AS UNSIGNED))
            LEFT JOIN wares w
                ON uhr.rewardable_type = 'App\\\\Models\\\\Ware' AND uhr.rewardable_id = w.id
            LEFT JOIN o_vips v
                ON uhr.rewardable_type = 'Modules\\\\Vip\\\\Entities\\\\OVip' AND uhr.rewardable_id = v.id
            LEFT JOIN badges b
                ON uhr.rewardable_type = 'Modules\\\\Badge\\\\Entities\\\\Badge' AND uhr.rewardable_id = b.id
            ");
            
        }
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_user_history_rewards");
    }
};
