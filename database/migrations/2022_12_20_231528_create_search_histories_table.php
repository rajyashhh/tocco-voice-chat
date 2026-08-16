<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('type')->default('2')->comment('1Official popular search 2User search history')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('search')->nullable();
            $table->unsignedTinyInteger('sort')->default('1')->comment('to sort')->nullable();
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
        Schema::dropIfExists('search_histories');
    }
}
