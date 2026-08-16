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
        Schema::table('user_coin_logs', function (Blueprint $table) {
            $table->decimal('helper_amount', 12, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('user_coin_logs', function (Blueprint $table) {
            $table->dropColumn('helper_amount');
        });
    }
};
