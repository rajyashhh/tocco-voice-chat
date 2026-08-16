<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cps', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('uid')->nullable();
            $table->unsignedInteger('wares_id')->comment('معرف الجوهرة');
            $table->unsignedInteger('num')->nullable();
            $table->unsignedInteger('user_id')->comment('المتلقي')->nullable();
            $table->unsignedInteger('fromUid')->comment('المانح')->nullable();
            $table->unsignedTinyInteger('status')->default('1')->comment('1 Guarding 2 Released 3 Waiting for the other party consent 4 Denied')->nullable();
            $table->unsignedInteger('exp')->comment('خبرة')->nullable();
            $table->unsignedInteger('addtime')->nullable();
            $table->unsignedInteger('agreetime')->nullable();
            $table->unsignedInteger('refusetime')->nullable();
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
        Schema::dropIfExists('cps');
    }
}
