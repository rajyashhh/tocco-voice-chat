<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFamiliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('families', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('is_success')->nullable ()->default (1);
            $table->string('image')->nullable ()->comment('avatar');
            $table->string('name');
            $table->string('introduce')->nullable();
            $table->string('notice')->comment('announcement')->nullable();
            $table->unsignedInteger('num')->comment('number of people')->nullable()->default (20);
            $table->unsignedInteger('user_id')->index()->comment('owner');
            $table->unsignedTinyInteger('speakswitch')->comment('Whether members are banned')->nullable();
            $table->unsignedTinyInteger('status')->default('1')->nullable();
            $table->unsignedInteger('update_user_id')->comment('editor')->nullable();
            $table->unsignedInteger('suctime')->comment('success time')->nullable();
            $table->unsignedInteger('start_time')->comment('Start time for less than 20 people')->nullable();
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
        Schema::dropIfExists('families');
    }
}
