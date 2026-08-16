<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('monthly_diamond_send')->default(0);
            $table->bigInteger('total_diamond_send')->default(0);
            $table->bigInteger('monthly_diamond_received')->default(0);
            $table->bigInteger('total_diamond_received')->default(0);
            $table->integer('sender_level')->default(0);
            $table->integer('received_level')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('monthly_diamond_send');
            $table->dropColumn('monthly_diamond_received');
            $table->dropColumn('sender_level');
            $table->dropColumn('received_level');
        });
    }
}
