<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTargetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_target', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('agency_id')->nullable();
            $table->unsignedInteger('union_id')->nullable();
            $table->unsignedInteger('family_id')->nullable();
            $table->unsignedInteger('target_id')->nullable();
            $table->double('target_diamonds')->nullable();
            $table->integer('add_month');
            $table->integer('add_year');
            $table->double('target_usd')->nullable();
            $table->integer('target_hours')->nullable();
            $table->integer('target_days')->nullable();
            $table->double('target_agency_share')->nullable();
            $table->double('user_diamonds')->nullable();
            $table->integer('user_hours')->nullable();
            $table->integer('user_days')->nullable();
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
        Schema::dropIfExists('user_target');
    }
}
