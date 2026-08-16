<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        // أنشئ مفتاح جديد باسم مختلف
        DB::statement("
            ALTER TABLE user_history_rewards 
            ADD UNIQUE uniq_user_rewards_v2 (user_id, sub_type, receive_type, rewardable_id, rewardable_type, is_deleted)
        ");

        // لا تقم بإعادة تسمية إذا كان المفتاح القديم موجود
        // لاحقًا يمكنك حذف المفتاح القديم بعد التأكد من عدم استخدامه
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE user_history_rewards DROP INDEX uniq_user_rewards_v2");
    }

};
