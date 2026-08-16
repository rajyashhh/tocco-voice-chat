<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_coin_payments', function (Blueprint $table) {

            $table->dropForeign('user_coin_payments_coin_id_foreign');

            $table->unsignedInteger('coin_id')->unsigned()->change();
            $table->foreign('coin_id')->references('id')->on('coins')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_coin_payments', function (Blueprint $table) {

        });
    }
};
