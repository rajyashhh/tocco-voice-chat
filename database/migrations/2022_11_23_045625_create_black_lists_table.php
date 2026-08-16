<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBlackListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('black_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('البادئ');
            $table->unsignedInteger('from_uid')->comment('مدرج في القائمة السوداء');
            $table->unsignedTinyInteger('status')->default('1')->comment('1 سحب الأسود 2 فتح الأسود')->nullable();
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
        Schema::dropIfExists('black_lists');
    }
}
