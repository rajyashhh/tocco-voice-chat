<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLevelLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_level_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger ('user_id')->nullable ()->default (0);
            $table->unsignedTinyInteger ('type')->nullable ()->default (0);
            $table->unsignedInteger ('level')->nullable ()->default (0);
            $table->unsignedBigInteger ('total')->nullable ()->default (0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_level_logs');
    }
}
