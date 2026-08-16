<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyVipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vips', function (Blueprint $table) {
            $table->dropColumn (['exp','di','co']);
        });

        Schema::table('vips', function (Blueprint $table) {
            $table->bigInteger ('exp')->nullable ()->default (0)->comment ('خبرة');
            $table->bigInteger ('di')->nullable ()->default (0)->comment ('ماسات');
            $table->bigInteger ('co')->nullable ()->default (0)->comment ('عملات');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vips', function (Blueprint $table) {
            //
        });
    }
}
