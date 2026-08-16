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
        Schema::create('weekly_cp_winners', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('weekly_cp_id');
            $table->unsignedInteger('user_one_id');
            $table->unsignedInteger('user_two_id');
            $table->integer('level');
            $table->string('type_relation');
            $table->double('total_price');
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
        Schema::dropIfExists('weekly_cp_winners');
    }
};
