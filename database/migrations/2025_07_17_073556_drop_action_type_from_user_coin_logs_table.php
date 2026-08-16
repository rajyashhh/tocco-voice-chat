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
            $table->dropColumn('action_type');
        });
    }

    public function down()
    {
        Schema::table('user_coin_logs', function (Blueprint $table) {
            $table->enum('action_type', ['add', 'subtract'])->default('add');
        });
    }
};
