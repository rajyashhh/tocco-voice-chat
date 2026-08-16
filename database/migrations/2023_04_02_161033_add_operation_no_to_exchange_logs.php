<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddOperationNoToExchangeLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exchange_logs', function (Blueprint $table) {
            $table->string ('operation_no')->unique ()->nullable ();
            $table->boolean ('status')->nullable ()->default (1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exchange_logs', function (Blueprint $table) {
            //
        });
    }
}
