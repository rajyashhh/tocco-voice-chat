<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $tables = [
            'gift_logs',
            'coin_game_users',
            'user_lucky_gifts',
         
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->bigInteger('app_profit_coins')->default(0);
                });
            }
        }
    }

    public function down()
    {
        $tables = [
            'gift_logs',
            'coin_game_users',
            'user_lucky_gifts',
           
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'app_profit_coins')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('app_profit_coins');
                });
            }
        }
    }
};
