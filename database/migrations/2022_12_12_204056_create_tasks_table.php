<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('type')->comment('1 novice task 2 daily tasks');
            $table->string('img')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('num')->default('1')->comment('Completions')->nullable();
            $table->unsignedInteger('jinbi')->default('5')->comment('Number of reward coins')->nullable();
            $table->unsignedTinyInteger('enable')->default('1')->comment('1 enable 2 disable')->nullable();
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
        Schema::dropIfExists('tasks');
    }
}
