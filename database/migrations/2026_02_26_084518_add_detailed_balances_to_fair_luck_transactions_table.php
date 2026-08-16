<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailedBalancesToFairLuckTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fair_luck_transactions', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('fair_luck_transactions', 'sender_balance_before')) {
                $blueprint->bigInteger('sender_balance_before')->nullable()->after('room_id');
            }
            if (!Schema::hasColumn('fair_luck_transactions', 'sender_balance_after')) {
                $blueprint->bigInteger('sender_balance_after')->nullable()->after('sender_balance_before');
            }
            if (!Schema::hasColumn('fair_luck_transactions', 'wallets_before')) {
                $blueprint->json('wallets_before')->nullable()->after('sender_balance_after');
            }
            if (!Schema::hasColumn('fair_luck_transactions', 'wallets_after')) {
                $blueprint->json('wallets_after')->nullable()->after('wallets_before');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fair_luck_transactions', function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'sender_balance_before',
                'sender_balance_after',
                'wallets_before',
                'wallets_after',
            ]);
        });
    }
}
