<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('not_fin_1')->comment('Unfinished tasks')->nullable();
            $table->string('fin_1')->comment('Number of tasks completed')->nullable();
            $table->string('receive_1')->comment('received')->nullable();
            $table->string('fin_2')->comment('Number of tasks completed')->nullable();
            $table->string('receive_2')->comment('received')->nullable();
            $table->unsignedTinyInteger('is_open')->comment('Whether to request this interface today 0 Not requested 1 Requested')->nullable();
            $table->unsignedInteger('addtime')->nullable();
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
        Schema::dropIfExists('user_tasks');
    }
}
