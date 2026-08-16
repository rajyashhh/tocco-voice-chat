<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        DB::statement("
            DELETE u1 FROM user_history_rewards u1
            INNER JOIN user_history_rewards u2 
            WHERE 
                u1.id > u2.id
                AND u1.user_id = u2.user_id
                AND u1.sub_type = u2.sub_type
                AND u1.receive_type = u2.receive_type
                AND u1.rewardable_id = u2.rewardable_id
                AND u1.rewardable_type = u2.rewardable_type
                AND u1.is_deleted = 0
                AND u2.is_deleted = 0
        ");

        Schema::table('user_history_rewards', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('user_history_rewards');

            if (isset($indexes['uniq_user_rewards'])) {
                $table->dropUnique('uniq_user_rewards');
            }

            $table->unique([
                'user_id',
                'sub_type',
                'receive_type',
                'rewardable_id',
                'rewardable_type',
                'is_deleted'
            ], 'uniq_user_rewards');
        });
    }

    public function down(): void
    {
        Schema::table('user_history_rewards', function (Blueprint $table) {
            $table->dropUnique('uniq_user_rewards');

            // إعادة المفتاح القديم بدون is_deleted
            $table->unique([
                'user_id',
                'sub_type',
                'receive_type',
                'rewardable_id',
                'rewardable_type',
            ], 'uniq_user_rewards');
        });
    }
};
