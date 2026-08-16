<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserUnionTjTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_union_tj', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('union_id')->index()->comment('guild id');
            $table->unsignedInteger('users_id')->comment('user id');
            $table->decimal('real_price')->comment('total amount')->nullable();
            $table->unsignedInteger('add_time')->comment('time');
            $table->unsignedInteger('add_time_month')->comment('month');
            $table->decimal('lw_price')->comment('total amount')->nullable();
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
        Schema::dropIfExists('user_union_tj');
    }
}
