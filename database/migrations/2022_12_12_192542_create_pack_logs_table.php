<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pack_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedTinyInteger('use_type')->default('1')->comment('1 normal use, 2 background deduction')->nullable();
            $table->unsignedTinyInteger('type')->nullable();
            $table->unsignedInteger('target_id')->comment('item id')->nullable();
            $table->unsignedInteger('get_nums')->nullable();
            $table->unsignedInteger('now_nums')->nullable();
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
        Schema::dropIfExists('pack_logs');
    }
}
