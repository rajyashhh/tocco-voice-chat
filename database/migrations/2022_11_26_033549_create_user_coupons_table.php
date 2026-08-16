<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_coupons', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('معرف المستخدم');
            $table->unsignedInteger('ware_id')->comment('معرف السلعة');
            $table->unsignedTinyInteger('status')->default('1')->comment('1 unused 2 used 3 expired')->nullable();
            $table->unsignedInteger('expire')->comment('انتهاء الصلاحية');
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
        Schema::dropIfExists('user_coupons');
    }
}
