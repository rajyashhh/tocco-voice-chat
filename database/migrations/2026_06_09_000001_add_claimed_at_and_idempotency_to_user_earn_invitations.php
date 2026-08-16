<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_earn_invitations', function (Blueprint $table) {
            $table->timestamp('claimed_at')->nullable()->after('is_claimed');
        });

        // Legacy rows were inserted with charge_id = 0 (no idempotency key). Null
        // them out so they don't collide on the new unique index — NULLs are exempt
        // from MySQL unique constraints.
        DB::table('user_earn_invitations')->where('charge_id', 0)->update(['charge_id' => null]);

        Schema::table('user_earn_invitations', function (Blueprint $table) {
            // Idempotency guard for charge-percentage earnings: a single charge can
            // only ever produce one inviter-commission row. (first_join_reward_* rows
            // pass charge_id = null and are de-duped by the UserCodeInvitation link.)
            $table->unique(['charge_id', 'source_type'], 'uniq_earn_charge_source');

            $table->index(['parent_id', 'is_claimed'], 'idx_earn_parent_claimed');
            $table->index(['user_id', 'source_type', 'is_claimed'], 'idx_earn_user_source_claimed');
        });
    }

    public function down()
    {
        Schema::table('user_earn_invitations', function (Blueprint $table) {
            $table->dropUnique('uniq_earn_charge_source');
            $table->dropIndex('idx_earn_parent_claimed');
            $table->dropIndex('idx_earn_user_source_claimed');
            $table->dropColumn('claimed_at');
        });
    }
};
